<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Services\ApiQuotaManager;
use Modules\Provisioning\Contracts\CarrierInterface;
use Modules\Provisioning\Data\CarrierResponse;
use Modules\Provisioning\Data\LineStatusResult;
use Modules\Provisioning\Data\TroubleTicketRequest;
use Modules\Provisioning\Data\WebhookResult;
use Modules\Provisioning\Exceptions\QuotaExceededException;
use Modules\Provisioning\Models\CarrierEventLog;
use Modules\Provisioning\Models\CarrierOrder;
use Modules\Provisioning\Services\Drivers\FiberCopDriver;
use Modules\Provisioning\Services\Drivers\GenericDriver;
use Modules\Provisioning\Services\Drivers\OpenFiberDriver;

/**
 * Orchestratore centrale per tutte le chiamate verso i carrier.
 *
 * Responsabilità:
 * - Risoluzione driver per carrier
 * - Controllo quota API (ApiQuotaManager)
 * - Cache per chiamate idempotenti
 * - Log ogni chiamata in carrier_events_log (SEMPRE)
 * - Retry policy: 0s → 5min → 30min → RetryFailed
 */
class CarrierGateway
{
    private const CACHE_TTL = [
        'line_test'      => 6 * 3600,   // 6 ore
        'order_status'   => 4 * 3600,   // 4 ore
        'status_zpoint'  => 2 * 3600,   // 2 ore
        'resource_status'=> 2 * 3600,   // 2 ore
    ];

    public function __construct(
        private readonly ApiQuotaManager  $quotaManager,
        private readonly OpenFiberDriver  $openFiberDriver,
        private readonly FiberCopDriver   $fiberCopDriver,
    ) {}

    // ---- Dispatch ordini ----

    public function sendActivationOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->callCarrier(
            carrier: $order->carrier,
            type: 'activation_order',
            order: $order,
            apiCall: fn() => $this->driver($order->carrier)->sendActivationOrder($order),
        );
    }

    public function sendChangeOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->callCarrier(
            carrier: $order->carrier,
            type: 'change_order',
            order: $order,
            apiCall: fn() => $this->driver($order->carrier)->sendChangeOrder($order),
        );
    }

    public function sendDeactivationOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->callCarrier(
            carrier: $order->carrier,
            type: 'deactivation_order',
            order: $order,
            apiCall: fn() => $this->driver($order->carrier)->sendDeactivationOrder($order),
        );
    }

    public function sendReschedule(CarrierOrder $order, Carbon $newDate): CarrierResponse
    {
        return $this->callCarrier(
            carrier: $order->carrier,
            type: 'reschedule',
            order: $order,
            apiCall: fn() => $this->driver($order->carrier)->sendReschedule($order, $newDate),
        );
    }

    public function sendUnsuspend(CarrierOrder $order): CarrierResponse
    {
        return $this->callCarrier(
            carrier: $order->carrier,
            type: 'unsuspend',
            order: $order,
            apiCall: fn() => $this->driver($order->carrier)->sendUnsuspend($order),
        );
    }

    // ---- Line Testing (con quota + cache) ----

    /**
     * Esegue line test.
     * OF v2.3: Header lt-api-key (NON tokenID come v2.2).
     *
     * @throws QuotaExceededException se quota giornaliera esaurita
     */
    public function checkLineStatus(string $carrier, string $resourceId): LineStatusResult
    {
        $type     = 'line_test';
        $cacheKey = "carrier:{$carrier}:{$type}:" . md5($resourceId);

        // 1. Quota check (NON è critical → differibile)
        if (!$this->quotaManager->canCall($carrier, $type)) {
            throw new QuotaExceededException($carrier, $type);
        }

        // 2. Cache check
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // 3. Esegui chiamata
        $start  = microtime(true);
        $result = $this->driver($carrier)->checkLineStatus($resourceId);
        $ms     = (int) ((microtime(true) - $start) * 1000);

        // 4. Consuma quota
        $this->quotaManager->consume($carrier, $type);

        // 5. Log
        $this->logEvent(
            tenantId: 1, // TODO: multi-tenant da context
            carrier: $carrier,
            direction: 'outbound',
            methodName: 'line_test',
            payload: json_encode(['resourceId' => $resourceId, 'result' => $result]),
            httpStatus: $result->success ? 200 : 422,
            ackNack: $result->success ? 'ack' : 'nack',
            durationMs: $ms,
        );

        // 6. Cache solo se OK
        if ($result->success && isset(self::CACHE_TTL[$type])) {
            Cache::put($cacheKey, $result, self::CACHE_TTL[$type]);
        }

        return $result;
    }

    // ---- Trouble Ticket ----

    public function openTroubleTicket(CarrierOrder $order, TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->callCarrier(
            carrier: $order->carrier,
            type: 'ticket_open',
            order: $order,
            apiCall: fn() => $this->driver($order->carrier)->openTroubleTicket($ticket),
        );
    }

    public function closeTroubleTicket(CarrierOrder $order, TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->callCarrier(
            carrier: $order->carrier,
            type: 'ticket_close',
            order: $order,
            apiCall: fn() => $this->driver($order->carrier)->closeTroubleTicket($ticket),
        );
    }

    // ---- Driver resolver ----

    public function driver(string $carrier): CarrierInterface
    {
        return match ($carrier) {
            'openfiber' => $this->openFiberDriver,
            'fibercop'  => $this->fiberCopDriver,
            default     => new GenericDriver($carrier),
        };
    }

    // ---- Core pattern chiamata carrier ----

    /**
     * Pattern standard per ogni chiamata carrier:
     * 1. Quota check (critical bypassa)
     * 2. Esegui chiamata
     * 3. consume quota
     * 4. Log in carrier_events_log (SEMPRE — anche su errore)
     * 5. Ritorna risultato
     */
    private function callCarrier(
        string $carrier,
        string $type,
        CarrierOrder $order,
        callable $apiCall,
    ): CarrierResponse {
        // 1. Quota
        if (!$this->quotaManager->canCall($carrier, $type)) {
            throw new QuotaExceededException($carrier, $type);
        }

        $start = microtime(true);

        try {
            // 2. Chiama driver
            /** @var CarrierResponse $result */
            $result = $apiCall();

            // 3. Consume quota
            $this->quotaManager->consume($carrier, $type);

            // 4. Log OK
            $ms = (int) ((microtime(true) - $start) * 1000);
            $this->logEvent(
                tenantId: $order->tenant_id,
                carrier: $carrier,
                direction: 'outbound',
                methodName: $type,
                payload: $result->rawPayload ?? '',
                httpStatus: $result->httpStatus,
                ackNack: $result->isAck() ? 'ack' : 'nack',
                durationMs: $ms,
                orderId: $order->id,
                codiceOrdineOlo: $order->codice_ordine_olo,
                errorMessage: $result->errorMessage,
            );

            return $result;

        } catch (\Throwable $e) {
            // Log eccezione
            $ms = (int) ((microtime(true) - $start) * 1000);
            $this->logEvent(
                tenantId: $order->tenant_id,
                carrier: $carrier,
                direction: 'outbound',
                methodName: $type,
                payload: null,
                httpStatus: 0,
                ackNack: 'error',
                durationMs: $ms,
                orderId: $order->id,
                codiceOrdineOlo: $order->codice_ordine_olo,
                errorMessage: $e->getMessage(),
            );

            throw $e;
        }
    }

    private function logEvent(
        int $tenantId,
        string $carrier,
        string $direction,
        string $methodName,
        ?string $payload,
        int $httpStatus,
        string $ackNack,
        int $durationMs,
        ?int $orderId = null,
        ?string $codiceOrdineOlo = null,
        ?string $errorMessage = null,
        ?string $sourceIp = null,
    ): void {
        CarrierEventLog::create([
            'tenant_id'       => $tenantId,
            'carrier'         => $carrier,
            'direction'       => $direction,
            'method_name'     => $methodName,
            'carrier_order_id'=> $orderId,
            'codice_ordine_olo'=> $codiceOrdineOlo,
            'payload'         => $payload,
            'http_status'     => $httpStatus,
            'ack_nack'        => $ackNack,
            'duration_ms'     => $durationMs,
            'error_message'   => $errorMessage,
            'source_ip'       => $sourceIp,
            'logged_at'       => now(),
        ]);
    }
}
