<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Contracts\Models\Contract;
use Modules\Network\Models\RadiusProfile;
use Modules\Network\Models\RadiusUser;
use Modules\Network\Models\RadiusSession;

/**
 * Gestisce gli utenti FreeRADIUS per autenticazione PPPoE/IPoE.
 *
 * FreeRADIUS usa il backend rlm_sql su PostgreSQL (stessa istanza).
 * Le credenziali sono nella tabella radius_users.
 *
 * In CARRIER_MOCK=true non viene eseguita alcuna chiamata esterna.
 */
class RadiusService
{
    private bool $isMocked;

    public function __construct()
    {
        $this->isMocked = (bool) config('app.carrier_mock', false);
    }

    /**
     * Crea l'utente RADIUS per un contratto appena attivato.
     * Username = email cliente (PPPoE) o MAC address (IPoE).
     *
     * @return RadiusUser
     */
    public function provisionUser(Contract $contract): RadiusUser
    {
        $contract->load(['customer', 'servicePlan']);

        $username = $this->deriveUsername($contract);
        $password = Str::random(16);

        $profile = $this->resolveProfile($contract);

        $radiusUser = RadiusUser::create([
            'tenant_id'         => $contract->tenant_id,
            'customer_id'       => $contract->customer_id,
            'contract_id'       => $contract->id,
            'username'          => $username,
            'password_hash'     => Hash::make($password),
            'auth_type'         => 'pap',
            'radius_profile_id' => $profile?->id,
            'status'            => 'active',
            'walled_garden'     => false,
        ]);

        if ($this->isMocked) {
            Log::info("[MOCK] RADIUS: utente creato {$username} per contratto #{$contract->id}");
        }

        // Notifica le credenziali al cliente (via NotificationService del modulo Contracts)
        // L'evento NetworkUserProvisioned viene dispatchato per il listener notifiche

        return $radiusUser;
    }

    /**
     * Elimina l'utente RADIUS a fronte di cessazione contratto.
     */
    public function deprovisionUser(Contract $contract): void
    {
        RadiusUser::forContract($contract->id)->delete();

        if ($this->isMocked) {
            Log::info("[MOCK] RADIUS: utente disabilitato per contratto #{$contract->id}");
        }
    }

    /**
     * Aggiorna il profilo di banda (es. dopo cambio piano).
     */
    public function updateProfile(RadiusUser $radiusUser, RadiusProfile $profile): void
    {
        $radiusUser->update(['radius_profile_id' => $profile->id]);

        if ($radiusUser->acct_session_id) {
            // Se l'utente ha una sessione attiva → CoA per applicare il nuovo profilo
            app(CoaService::class)->changeBandwidth($radiusUser, $profile);
        }
    }

    /**
     * Registra l'accounting start (ricevuto da FreeRADIUS via API/webhook).
     */
    public function accountingStart(array $attrs): RadiusSession
    {
        $radiusUser = RadiusUser::where('username', $attrs['username'])->first();

        if (empty($attrs['Acct-Session-Id'])) {
            Log::warning("RADIUS accounting start: Acct-Session-Id mancante per {$attrs['username']} (RFC 2866 violation)");
        }

        $session = RadiusSession::create([
            'tenant_id'          => $radiusUser?->tenant_id ?? 0,
            'radius_user_id'     => $radiusUser?->id,
            'username'           => $attrs['username'],
            'nas_ip'             => $attrs['NAS-IP-Address'] ?? '',
            'nas_port_id'        => $attrs['NAS-Port-Id'] ?? null,
            'framed_ip'          => $attrs['Framed-IP-Address'] ?? null,
            'acct_session_id'    => $attrs['Acct-Session-Id'] ?? Str::uuid(),
            'acct_start'         => now(),
            'calling_station_id' => $attrs['Calling-Station-Id'] ?? null,
            'called_station_id'  => $attrs['Called-Station-Id'] ?? null,
            'retention_until'    => now()->addYears(6)->toDateString(),
        ]);

        // Aggiorna cache sessione attiva sull'utente RADIUS
        if ($radiusUser) {
            $radiusUser->update([
                'nas_ip'          => $attrs['NAS-IP-Address'] ?? null,
                'framed_ip'       => $attrs['Framed-IP-Address'] ?? null,
                'acct_session_id' => $attrs['Acct-Session-Id'] ?? null,
                'last_auth_at'    => now(),
            ]);
        }

        return $session;
    }

    /**
     * Registra l'accounting stop (Decreto Pisanu — obbligatorio).
     */
    public function accountingStop(array $attrs): void
    {
        $session = RadiusSession::where('acct_session_id', $attrs['Acct-Session-Id'] ?? '')
            ->whereNull('acct_stop')
            ->latest()
            ->first();

        if (!$session) {
            Log::warning("RADIUS accounting stop: sessione non trovata per {$attrs['Acct-Session-Id']}");
            return;
        }

        $session->update([
            'acct_stop'           => now(),
            'acct_session_time'   => $attrs['Acct-Session-Time'] ?? 0,
            'acct_input_octets'   => $attrs['Acct-Input-Octets'] ?? 0,
            'acct_output_octets'  => $attrs['Acct-Output-Octets'] ?? 0,
            'acct_terminate_cause' => $attrs['Acct-Terminate-Cause'] ?? null,
        ]);

        // Pulisci la sessione attiva sull'utente
        RadiusUser::where('acct_session_id', $attrs['Acct-Session-Id'])
            ->update(['acct_session_id' => null, 'framed_ip' => null]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function deriveUsername(Contract $contract): string
    {
        // PPPoE: usa l'email del cliente
        return $contract->customer->email;
    }

    private function resolveProfile(Contract $contract): ?RadiusProfile
    {
        $plan = $contract->servicePlan;

        // Cerca un profilo con nome corrispondente alla tecnologia e banda
        $profileName = strtoupper($plan->technology) . '_' . $plan->bandwidth_dl . 'M';

        return RadiusProfile::where('tenant_id', $contract->tenant_id)
            ->where('name', $profileName)
            ->active()
            ->first();
    }
}
