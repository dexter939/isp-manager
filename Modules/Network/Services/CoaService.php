<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Facades\Log;
use Modules\Network\Jobs\SendCoaRequestJob;
use Modules\Network\Models\RadiusProfile;
use Modules\Network\Models\RadiusUser;

/**
 * Change of Authorization (CoA) per FreeRADIUS — RFC 5176.
 *
 * Invia pacchetti CoA-Request o Disconnect-Request al NAS
 * tramite radclient (binario FreeRADIUS) o socket UDP.
 *
 * Attributi Mikrotik:
 *   CoA modifica banda: Mikrotik-Rate-Limit
 *   Disconnect:         standard Disconnect-Request
 *
 * Walled Garden:
 *   Dopo CoA, il traffico HTTP viene rediretto a https://paga.{isp}.it/{token}
 *   Sblocco automatico via CoA restore dopo PaymentReceived event.
 */
class CoaService
{
    private bool $isMocked;
    private string $coaSecret;
    private int $coaPort;

    public function __construct()
    {
        $this->isMocked  = (bool) config('app.carrier_mock', false);
        $this->coaSecret = config('network.radius_coa_secret', 'testing123');
        $this->coaPort   = (int) config('network.radius_coa_port', 3799);
    }

    /**
     * Sospende l'accesso internet del cliente → Walled Garden.
     * Invia CoA-Request con Mikrotik-Rate-Limit ridotto + redirect.
     *
     * Chiamato da: DunningService al giorno D+25.
     */
    public function suspendToWalledGarden(RadiusUser $radiusUser): void
    {
        $token = bin2hex(random_bytes(16));

        if (!$this->hasActiveSession($radiusUser)) {
            Log::warning("CoA suspend: nessuna sessione attiva per radiusUser #{$radiusUser->id}");
            $radiusUser->update(['status' => 'suspended', 'walled_garden' => true, 'walled_garden_token' => $token]);
            return;
        }

        $radiusUser->update([
            'status'               => 'suspended',
            'walled_garden'        => true,
            'walled_garden_token'  => $token,
        ]);

        $profile = $radiusUser->profile;
        $walledLimit = $profile
            ? $profile->mikrotikWalledGardenLimit()
            : '128k/128k';

        $attrs = [
            'NAS-IP-Address'       => $radiusUser->nas_ip,
            'Acct-Session-Id'      => $radiusUser->acct_session_id,
            'Mikrotik-Rate-Limit'  => $walledLimit,
            'Mikrotik-Recv-Limit'  => '10000000',
        ];

        $this->sendCoaRequest($radiusUser->nas_ip, $attrs, 'CoA-Request');

        Log::info("CoA: walled garden attivato per radiusUser #{$radiusUser->id} (token={$token})");
    }

    /**
     * Ripristina l'accesso internet dopo il pagamento.
     * Chiamato da: NetworkListener su evento PaymentReceived.
     */
    public function restoreAccess(RadiusUser $radiusUser): void
    {
        if (!$radiusUser->isSuspended()) {
            return;
        }

        $radiusUser->update([
            'status'              => 'active',
            'walled_garden'       => false,
            'walled_garden_token' => null,
        ]);

        if (!$this->hasActiveSession($radiusUser)) {
            // Nessuna sessione attiva — l'accesso verrà ripristinato al prossimo login
            Log::info("CoA restore: nessuna sessione attiva, accesso ripristinato al prossimo login (radiusUser #{$radiusUser->id})");
            return;
        }

        $profile = $radiusUser->profile;
        $normalLimit = $profile?->mikrotikRateLimit() ?? '100M/10M';

        $attrs = [
            'NAS-IP-Address'      => $radiusUser->nas_ip,
            'Acct-Session-Id'     => $radiusUser->acct_session_id,
            'Mikrotik-Rate-Limit' => $normalLimit,
        ];

        $this->sendCoaRequest($radiusUser->nas_ip, $attrs, 'CoA-Request');

        Log::info("CoA: accesso ripristinato per radiusUser #{$radiusUser->id}");
    }

    /**
     * Disconnette forzatamente la sessione (cessazione contratto).
     * Invia Disconnect-Request al NAS.
     */
    public function disconnect(RadiusUser $radiusUser): void
    {
        if (!$this->hasActiveSession($radiusUser)) {
            Log::info("CoA disconnect: nessuna sessione attiva per radiusUser #{$radiusUser->id}");
            return;
        }

        $attrs = [
            'NAS-IP-Address' => $radiusUser->nas_ip,
            'Acct-Session-Id' => $radiusUser->acct_session_id,
        ];

        $this->sendCoaRequest($radiusUser->nas_ip, $attrs, 'Disconnect-Request');

        Log::info("CoA disconnect inviato per radiusUser #{$radiusUser->id}");
    }

    /**
     * Modifica la banda di una sessione attiva (es. upgrade piano).
     */
    public function changeBandwidth(RadiusUser $radiusUser, RadiusProfile $profile): void
    {
        if (!$this->hasActiveSession($radiusUser)) {
            return;
        }

        $attrs = [
            'NAS-IP-Address'      => $radiusUser->nas_ip,
            'Acct-Session-Id'     => $radiusUser->acct_session_id,
            'Mikrotik-Rate-Limit' => $profile->mikrotikRateLimit(),
        ];

        $this->sendCoaRequest($radiusUser->nas_ip, $attrs, 'CoA-Request');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function hasActiveSession(RadiusUser $radiusUser): bool
    {
        return (bool) $radiusUser->nas_ip && (bool) $radiusUser->acct_session_id;
    }

    /**
     * Dispatches a queued job to send the CoA packet — avoids blocking the Octane worker.
     *
     * @param array<string, string> $attrs
     */
    private function sendCoaRequest(string $nasIp, array $attrs, string $packetType): void
    {
        if ($this->isMocked) {
            Log::info("[MOCK] CoA {$packetType} → {$nasIp}", $attrs);
            return;
        }

        SendCoaRequestJob::dispatch($nasIp, $attrs, $packetType, $this->coaSecret, $this->coaPort);
    }
}
