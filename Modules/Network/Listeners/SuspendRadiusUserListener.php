<?php

declare(strict_types=1);

namespace Modules\Network\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Contracts\Events\ContractStatusChanged;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Network\Models\RadiusUser;
use Modules\Network\Services\CoaService;

/**
 * Listener: ContractStatusChanged (→ Suspended) → CoA Walled Garden.
 *
 * Quando un contratto viene sospeso (per morosità o operatore),
 * applica il walled garden tramite CoA RADIUS RFC 5176.
 */
class SuspendRadiusUserListener
{
    public function __construct(
        private readonly CoaService $coaService,
    ) {}

    public function handle(ContractStatusChanged $event): void
    {
        if ($event->newStatus !== ContractStatus::Suspended) {
            return;
        }

        $radiusUser = RadiusUser::forContract($event->contract->id)
            ->active()
            ->first();

        if (!$radiusUser) {
            Log::info("ContractSuspended: nessun utente RADIUS per contratto #{$event->contract->id}");
            return;
        }

        try {
            $this->coaService->suspendToWalledGarden($radiusUser);
        } catch (\Throwable $e) {
            Log::error("CoA suspend fallito per contratto #{$event->contract->id}: {$e->getMessage()}");
        }
    }
}
