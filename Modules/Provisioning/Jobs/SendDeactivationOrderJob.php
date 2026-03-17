<?php

declare(strict_types=1);

namespace Modules\Provisioning\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Models\CarrierOrder;
use Modules\Provisioning\Services\CarrierGateway;
use Modules\Provisioning\Services\OrderStateMachine;
use Modules\Provisioning\Services\VlanManager;

/**
 * Invia ordine di cessazione al carrier (OLO_DeactivationOrder).
 * Alla cessazione completata: rilascia il C-VLAN dal pool.
 */
class SendDeactivationOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 60;
    public string $queue = 'carrier-orders';

    public function __construct(
        public readonly int $carrierOrderId,
    ) {}

    public function handle(
        CarrierGateway    $gateway,
        OrderStateMachine $stateMachine,
        VlanManager       $vlanManager,
    ): void {
        $order = CarrierOrder::with('contract')->findOrFail($this->carrierOrderId);

        Log::info("Invio ordine cessazione #{$order->id} carrier={$order->carrier}");

        try {
            $response = $gateway->sendDeactivationOrder($order);
        } catch (\Throwable $e) {
            $stateMachine->handleSendFailure($order, $e->getMessage());
            return;
        }

        if ($response->isAck()) {
            if ($response->carrierId) {
                $order->update(['codice_ordine_of' => $response->carrierId]);
            }
            $stateMachine->markSent($order);

            // Rilascia VLAN subito dopo ACK cessazione
            $vlanManager->release($order->contract);
        } else {
            $stateMachine->handleSendFailure($order, $response->errorCode . ': ' . ($response->errorMessage ?? ''));
        }
    }
}
