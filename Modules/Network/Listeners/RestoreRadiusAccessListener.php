<?php

declare(strict_types=1);

namespace Modules\Network\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Events\PaymentReceived;
use Modules\Network\Models\RadiusUser;
use Modules\Network\Services\CoaService;
use Modules\Network\Services\WalledGardenService;

/**
 * Listener: PaymentReceived → ripristina accesso CoA.
 *
 * Quando il cliente paga la fattura scaduta, sblocca automaticamente
 * il walled garden tramite CoA RADIUS RFC 5176.
 */
class RestoreRadiusAccessListener
{
    public function __construct(
        private readonly CoaService $coaService,
        private readonly WalledGardenService $walledGarden,
    ) {}

    public function handle(PaymentReceived $event): void
    {
        $contractId = $event->invoice->contract_id;

        $radiusUser = RadiusUser::forContract($contractId)
            ->where('walled_garden', true)
            ->first();

        if (!$radiusUser) {
            return; // Nessuna sospensione attiva — niente da fare
        }

        try {
            $this->coaService->restoreAccess($radiusUser);
            $this->walledGarden->invalidateToken($radiusUser);

            Log::info("RADIUS: accesso ripristinato per contratto #{$contractId} dopo pagamento fattura #{$event->invoice->id}");
        } catch (\Throwable $e) {
            Log::error("CoA restore fallito per contratto #{$contractId}: {$e->getMessage()}");
        }
    }
}
