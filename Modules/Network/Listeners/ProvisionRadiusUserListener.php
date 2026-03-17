<?php

declare(strict_types=1);

namespace Modules\Network\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Contracts\Events\ContractSigned;
use Modules\Network\Services\RadiusService;

/**
 * Listener: ContractSigned → crea utente RADIUS.
 *
 * Quando un contratto viene firmato e attivato,
 * provisiona automaticamente le credenziali PPPoE su FreeRADIUS.
 */
class ProvisionRadiusUserListener
{
    public function __construct(
        private readonly RadiusService $radiusService,
    ) {}

    public function handle(ContractSigned $event): void
    {
        try {
            $radiusUser = $this->radiusService->provisionUser($event->contract);
            Log::info("RADIUS: utente creato #{$radiusUser->id} per contratto #{$event->contract->id}");
        } catch (\Throwable $e) {
            Log::error("RADIUS provision fallito per contratto #{$event->contract->id}: {$e->getMessage()}");
        }
    }
}
