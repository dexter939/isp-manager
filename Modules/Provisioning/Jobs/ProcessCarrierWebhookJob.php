<?php

declare(strict_types=1);

namespace Modules\Provisioning\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Models\CarrierEventLog;
use Modules\Provisioning\Models\CarrierOrder;
use Modules\Provisioning\Services\CarrierGateway;
use Modules\Provisioning\Services\OrderStateMachine;

/**
 * Processa il payload di un webhook carrier in modo asincrono.
 *
 * Flow:
 * 1. Parsifica payload via driver carrier
 * 2. Trova ordine per codice_olo
 * 3. Aggiorna stato via OrderStateMachine
 * 4. Logga in carrier_events_log
 */
class ProcessCarrierWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly string $carrier,
        public readonly string $payload,
        public readonly string $sourceIp,
    ) {}

    public function handle(
        CarrierGateway $gateway,
        OrderStateMachine $stateMachine,
    ): void {
        // 1. Parse via driver appropriato
        $fakeRequest = \Illuminate\Http\Request::create('/', 'POST', [], [], [], [], $this->payload);
        $result = $gateway->driver($this->carrier)->handleInboundWebhook($fakeRequest);

        if (!$result->parsed) {
            Log::error("Webhook {$this->carrier}: parsing fallito", [
                'error'  => $result->errorMessage,
                'ip'     => $this->sourceIp,
            ]);
            return;
        }

        // 2. Trova ordine
        $order = null;
        if ($result->codiceOrdineOlo) {
            $order = CarrierOrder::where('codice_ordine_olo', $result->codiceOrdineOlo)->first();
        }
        if (!$order && $result->codiceOrdineOf) {
            $order = CarrierOrder::where('codice_ordine_of', $result->codiceOrdineOf)->first();
        }

        if (!$order) {
            Log::warning("Webhook {$this->carrier}: ordine non trovato", [
                'codice_olo' => $result->codiceOrdineOlo,
                'codice_of'  => $result->codiceOrdineOf,
                'type'       => $result->messageType,
            ]);
        }

        // 3. Log in carrier_events_log (SEMPRE, anche se ordine non trovato)
        CarrierEventLog::create([
            'tenant_id'         => $order?->tenant_id ?? 1,
            'carrier'           => $this->carrier,
            'direction'         => 'inbound',
            'method_name'       => $result->messageType,
            'carrier_order_id'  => $order?->id,
            'codice_ordine_olo' => $result->codiceOrdineOlo,
            'payload'           => $this->payload,
            'http_status'       => 200,
            'ack_nack'          => 'ack',
            'source_ip'         => $this->sourceIp,
            'logged_at'         => now(),
        ]);

        // 4. Aggiorna stato ordine
        if ($order && $result->newState !== null) {
            try {
                $stateMachine->processWebhookResult($order, $result);
            } catch (\LogicException $e) {
                Log::warning("OrderStateMachine: {$e->getMessage()}");
            }
        }
    }
}
