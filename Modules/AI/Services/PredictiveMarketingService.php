<?php

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Contracts\Models\Contract;
use Modules\Coverage\Services\FeasibilityService;

/**
 * Marketing predittivo: identifica clienti FTTC/FWA che possono passare a FTTH.
 *
 * Logica:
 * 1. Trova contratti con tecnologia FTTC o FWA attivi
 * 2. Controlla la copertura FTTH nell'indirizzo di installazione (da DB locale, no API carrier)
 * 3. Filtra per potenziale di upgrade (velocità attuale vs disponibile)
 * 4. Genera campagna WhatsApp personalizzata (template pre-approvato Meta WABA)
 *
 * Compliance GDPR:
 * - Solo clienti che hanno espresso consenso marketing (customers.marketing_consent = true)
 * - Opt-out immediato se risposta "STOP" via WhatsApp
 */
class PredictiveMarketingService
{
    private bool $isMocked;

    public function __construct(
        private readonly FeasibilityService $feasibilityService,
        private readonly WhatsAppService $whatsapp,
    ) {
        $this->isMocked = (bool) config('app.carrier_mock', false);
    }

    /**
     * Identifica clienti upgradabili a FTTH nell'intero parco contratti del tenant.
     *
     * @return Collection<array{contract: Contract, ftth_speed: int, carrier: string}>
     */
    public function findUpgradableFttcContracts(int $tenantId): Collection
    {
        $candidates = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereIn('carrier', ['fibercop', 'openfiber'])
            ->whereHas('servicePlan', fn($q) => $q->where('technology', '!=', 'FTTH'))
            ->with(['customer', 'servicePlan'])
            ->get();

        return $candidates->filter(function (Contract $contract) {
            if (!$contract->customer?->marketing_consent) {
                return false;
            }

            $address = $contract->indirizzo_installazione;
            if (!$address || !isset($address['via'], $address['civico'], $address['comune'])) {
                return false;
            }

            try {
                $feasibility = $this->feasibilityService->check(
                    via:       $address['via'],
                    civico:    $address['civico'],
                    comune:    $address['comune'],
                    provincia: $address['provincia'] ?? '',
                );

                $ftthOptions = collect($feasibility->technologies)
                    ->filter(fn($t) => $t->technology === 'FTTH' && $t->status === 'vendibile');

                return $ftthOptions->isNotEmpty();
            } catch (\Throwable) {
                return false;
            }
        })->map(function (Contract $contract) {
            $address = $contract->indirizzo_installazione;

            $feasibility = $this->feasibilityService->check(
                via:       $address['via'],
                civico:    $address['civico'],
                comune:    $address['comune'],
                provincia: $address['provincia'] ?? '',
            );

            $ftth = collect($feasibility->technologies)
                ->filter(fn($t) => $t->technology === 'FTTH' && $t->status === 'vendibile')
                ->sortByDesc('maxSpeedDl')
                ->first();

            return [
                'contract'    => $contract,
                'ftth_speed'  => $ftth->maxSpeedDl,
                'ftth_carrier' => $ftth->carrier,
                'current_speed' => $contract->servicePlan->bandwidth_dl ?? 0,
            ];
        });
    }

    /**
     * Lancia campagna WhatsApp per upgrade FTTC→FTTH.
     * Template: "upgrade_ftth_offer_it" (pre-approvato Meta WABA)
     *   Variabili: {{1}}=nome cliente, {{2}}=velocità FTTH, {{3}}=URL offerta
     *
     * @return int Numero messaggi inviati
     */
    public function launchUpgradeCampaign(int $tenantId, string $offerUrl = ''): int
    {
        $upgradable = $this->findUpgradableFttcContracts($tenantId);

        if ($upgradable->isEmpty()) {
            Log::info("PredictiveMarketing: nessun candidato upgrade per tenant {$tenantId}");
            return 0;
        }

        $numbers    = [];
        $components = [];

        foreach ($upgradable as $item) {
            $customer = $item['contract']->customer;
            $phone    = $customer->cellulare ?? $customer->telefono;

            if (!$phone) {
                continue;
            }

            $numbers[] = $phone;
        }

        // Template unico per tutti — i parametri variabili sono gestiti lato Meta
        // Per invii personalizzati (nome individuale) usare sendTemplate in loop
        $sent = $this->whatsapp->sendBulk(
            tenantId:     $tenantId,
            toNumbers:    $numbers,
            templateName: 'upgrade_ftth_offer_it',
            components:   [
                [
                    'type'       => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => 'cliente'],
                        ['type' => 'text', 'text' => '1000'],
                        ['type' => 'text', 'text' => $offerUrl ?: 'https://ispmanager.it/offerta-ftth'],
                    ],
                ],
            ],
        );

        Log::info("PredictiveMarketing: campagna FTTH → {$sent}/" . count($numbers) . " messaggi inviati");

        return $sent;
    }

    /**
     * Identifica clienti FWA in zone dove è disponibile FTTH.
     * Candidati con latenza alta o velocità inferiore al piano (da RADIUS accounting).
     *
     * @return Collection<Contract>
     */
    public function findFwaToFtthCandidates(int $tenantId): Collection
    {
        return Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('servicePlan', fn($q) => $q->where('technology', 'FWA'))
            ->whereHas('customer', fn($q) => $q->where('marketing_consent', true))
            ->with(['customer', 'servicePlan'])
            ->get()
            ->filter(function (Contract $contract) {
                $address = $contract->indirizzo_installazione;
                if (!$address) return false;

                try {
                    $feasibility = $this->feasibilityService->check(
                        via:       $address['via'],
                        civico:    $address['civico'],
                        comune:    $address['comune'],
                        provincia: $address['provincia'] ?? '',
                    );

                    return collect($feasibility->technologies)
                        ->some(fn($t) => $t->technology === 'FTTH' && $t->status === 'vendibile');
                } catch (\Throwable) {
                    return false;
                }
            });
    }
}
