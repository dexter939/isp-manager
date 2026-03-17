<?php

declare(strict_types=1);

namespace Modules\Contracts\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Contracts\Events\ContractSigned;

class SendContractSignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public function handle(ContractSigned $event): void
    {
        $contract = $event->contract->load(['customer', 'servicePlan']);

        Log::info("Contratto #{$contract->id} firmato da cliente #{$contract->customer_id}", [
            'signed_at' => $contract->signed_at,
            'signed_ip' => $contract->signed_ip,
        ]);

        // TODO Fase 3 (Billing): avvia provisioning ordine carrier
        // TODO Fase 5 (AI): invia messaggio WhatsApp di benvenuto
    }
}
