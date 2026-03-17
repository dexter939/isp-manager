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
 * Invia ordine di attivazione al carrier.
 *
 * Flow:
 * 1. Assegna C-VLAN dal pool
 * 2. Invia OLO_ActivationSetup al carrier via CarrierGateway
 * 3. Se ACK → markSent() + salva codice_ordine_of
 * 4. Se NACK/errore → handleSendFailure() → schedula retry
 */
class SendActivationOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;  // Il retry è gestito da OrderStateMachine/RetryFailedOrderJob
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
        $order = CarrierOrder::with(['contract.customer', 'contract.servicePlan'])->findOrFail($this->carrierOrderId);

        Log::info("Invio ordine attivazione #{$order->id} carrier={$order->carrier}");

        // 1. Assegna VLAN se non già assegnata
        if (!$order->vlan_pool_id) {
            $vlan = $vlanManager->assign($order->carrier, $order->contract);
            $order->update(['vlan_pool_id' => $vlan->id, 'cvlan' => (string) $vlan->vlan_id]);
        }

        // 2. Invia al carrier
        try {
            $response = $gateway->sendActivationOrder($order);
        } catch (\Throwable $e) {
            $stateMachine->handleSendFailure($order, $e->getMessage());
            return;
        }

        if ($response->isAck()) {
            // 3. ACK: aggiorna codice_ordine_of e marca come sent
            if ($response->carrierId) {
                $order->update(['codice_ordine_of' => $response->carrierId]);
            }
            $stateMachine->markSent($order);
        } else {
            // 4. NACK: schedula retry
            $stateMachine->handleSendFailure(
                $order,
                $response->errorCode . ': ' . ($response->errorMessage ?? 'NACK dal carrier')
            );
        }
    }
}
