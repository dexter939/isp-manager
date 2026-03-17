<?php

declare(strict_types=1);

namespace Modules\Network\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Contracts\Events\ContractStatusChanged;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Network\Models\RadiusUser;
use Modules\Network\Services\CoaService;
use Modules\Network\Services\RadiusService;

/**
 * Listener: ContractStatusChanged (→ Terminated) → deprovision RADIUS + Disconnect.
 *
 * Quando un contratto viene cessato, invia Disconnect-Request al NAS
 * e disabilita l'utente RADIUS.
 */
class DeprovisionRadiusUserListener
{
    public function __construct(
        private readonly RadiusService $radiusService,
        private readonly CoaService $coaService,
    ) {}

    public function handle(ContractStatusChanged $event): void
    {
        if ($event->newStatus !== ContractStatus::Terminated) {
            return;
        }

        $radiusUser = RadiusUser::forContract($event->contract->id)->first();

        if (!$radiusUser) {
            return;
        }

        try {
            // Disconnetti la sessione attiva
            if ($radiusUser->acct_session_id) {
                $this->coaService->disconnect($radiusUser);
            }

            // Rimuovi l'utente RADIUS
            $this->radiusService->deprovisionUser($event->contract);

            Log::info("RADIUS: deprovisioning completato per contratto #{$event->contract->id}");
        } catch (\Throwable $e) {
            Log::error("RADIUS deprovision fallito per contratto #{$event->contract->id}: {$e->getMessage()}");
        }
    }
}
