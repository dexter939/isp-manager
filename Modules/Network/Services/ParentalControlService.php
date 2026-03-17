<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Network\Enums\ParentalControlStatus;
use Modules\Network\Events\ParentalControlActivated;
use Modules\Network\Events\ParentalControlSuspended;
use Modules\Network\Models\ParentalControlProfile;
use Modules\Network\Models\ParentalControlSubscription;
use Modules\Network\Services\DnsFilter\DnsFilterResolverInterface;

class ParentalControlService
{
    public function __construct(
        private readonly DnsFilterResolverInterface $resolver,
        private readonly RadiusService $radiusService,
    ) {}

    /**
     * Attiva il Parental Control per un account PPPoE.
     * Aggiunge gli attributi DNS a radreply, sincronizza il resolver e
     * dispatcha l'evento di attivazione.
     */
    public function activateForAccount(
        string $customerId,
        string $pppoeAccountId,
        string $profileId,
        string $tenantId,
    ): ParentalControlSubscription {
        return DB::transaction(function () use ($customerId, $pppoeAccountId, $profileId, $tenantId): ParentalControlSubscription {
            $subscription = ParentalControlSubscription::create([
                'tenant_id'        => $tenantId,
                'customer_id'      => $customerId,
                'pppoe_account_id' => $pppoeAccountId,
                'profile_id'       => $profileId,
                'status'           => ParentalControlStatus::Active,
                'activated_at'     => now(),
            ]);

            // Add DNS override attributes to radreply for this PPPoE account
            $this->addRadiusDnsAttributes($pppoeAccountId);

            // Sync subscription with DNS filter backend
            $this->resolver->syncSubscription($subscription);

            event(new ParentalControlActivated($subscription));

            return $subscription;
        });
    }

    /**
     * Sospende una subscription di Parental Control.
     * Rimuove gli attributi DNS da radreply e dispatcha l'evento.
     */
    public function suspendSubscription(ParentalControlSubscription $subscription): void
    {
        DB::transaction(function () use ($subscription): void {
            // Restore original DNS — remove the DNS proxy override from radreply
            if ($subscription->pppoe_account_id) {
                $this->removeRadiusDnsAttributes($subscription->pppoe_account_id);
            }

            $subscription->update([
                'status'       => ParentalControlStatus::Suspended,
                'suspended_at' => now(),
            ]);

            event(new ParentalControlSuspended($subscription));
        });
    }

    /**
     * Aggiorna le liste custom del cliente e le sincronizza col resolver.
     */
    public function updateCustomerFilters(
        ParentalControlSubscription $subscription,
        array $blacklist,
        array $whitelist,
    ): void {
        $subscription->update([
            'customer_custom_blacklist' => $blacklist,
            'customer_custom_whitelist' => $whitelist,
        ]);

        $this->resolver->syncSubscription($subscription);
    }

    /**
     * Scarica la lista AGCOM e aggiorna tutti i profili agcom_compliant=true
     * aggiungendo i domini alla custom_blacklist.
     */
    public function syncAgcomList(): void
    {
        if (config('app.carrier_mock')) {
            Log::info('[MOCK] ParentalControlService::syncAgcomList — skip HTTP, nessun aggiornamento effettuato');
            return;
        }

        $url = config('parental_control.agcom_list_url');

        if (empty($url)) {
            Log::warning('ParentalControlService::syncAgcomList — AGCOM_LIST_URL non configurato');
            return;
        }

        $response = Http::get($url);

        if ($response->failed()) {
            Log::error("ParentalControlService::syncAgcomList — HTTP error {$response->status()} da {$url}");
            return;
        }

        $agcomDomains = collect(explode("\n", $response->body()))
            ->map(fn(string $line) => trim($line))
            ->filter(fn(string $line) => $line !== '' && !str_starts_with($line, '#'))
            ->unique()
            ->values()
            ->all();

        if (empty($agcomDomains)) {
            Log::warning('ParentalControlService::syncAgcomList — lista AGCOM vuota o non parsabile');
            return;
        }

        $profiles = ParentalControlProfile::where('agcom_compliant', true)->get();

        foreach ($profiles as $profile) {
            $existing = $profile->custom_blacklist ?? [];
            $merged   = array_values(array_unique(array_merge($existing, $agcomDomains)));
            $profile->update(['custom_blacklist' => $merged]);

            Log::info(
                "ParentalControlService::syncAgcomList — profilo {$profile->id} aggiornato con " . count($agcomDomains) . ' domini AGCOM'
            );
        }
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Aggiunge gli attributi DNS proxy a radreply per l'account PPPoE indicato.
     * Il RadiusService gestisce la logica di scrittura su radreply.
     */
    private function addRadiusDnsAttributes(string $pppoeAccountId): void
    {
        $primary   = config('parental_control.dns_proxy_primary');
        $secondary = config('parental_control.dns_proxy_secondary');

        // RadiusService::addReplyAttributes() gestisce l'upsert su radreply
        $this->radiusService->addReplyAttributes($pppoeAccountId, [
            'DNS-Server-Primary'   => $primary,
            'DNS-Server-Secondary' => $secondary,
        ]);
    }

    /**
     * Rimuove gli attributi DNS proxy da radreply per l'account PPPoE indicato.
     */
    private function removeRadiusDnsAttributes(string $pppoeAccountId): void
    {
        $this->radiusService->removeReplyAttributes($pppoeAccountId, [
            'DNS-Server-Primary',
            'DNS-Server-Secondary',
        ]);
    }
}
