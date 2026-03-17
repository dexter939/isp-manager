<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services\Drivers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Contracts\CarrierInterface;
use Modules\Provisioning\Data\CarrierResponse;
use Modules\Provisioning\Data\LineStatusResult;
use Modules\Provisioning\Data\TroubleTicketRequest;
use Modules\Provisioning\Data\WebhookResult;
use Modules\Provisioning\Models\CarrierOrder;

/**
 * Driver generico per carrier configurabili da DB.
 * Usato per carrier non ufficialmente supportati (es: operatori regionali).
 * In CARRIER_MOCK=true (default per driver generico): logga senza inviare.
 */
class GenericDriver implements CarrierInterface
{
    public function __construct(private readonly string $carrier) {}

    public function sendActivationOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->mock('sendActivationOrder', $order->codice_ordine_olo);
    }

    public function sendChangeOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->mock('sendChangeOrder', $order->codice_ordine_olo);
    }

    public function sendDeactivationOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->mock('sendDeactivationOrder', $order->codice_ordine_olo);
    }

    public function sendReschedule(CarrierOrder $order, Carbon $newDate): CarrierResponse
    {
        return $this->mock('sendReschedule', $order->codice_ordine_olo);
    }

    public function sendUnsuspend(CarrierOrder $order): CarrierResponse
    {
        return $this->mock('sendUnsuspend', $order->codice_ordine_olo);
    }

    public function checkLineStatus(string $resourceId): LineStatusResult
    {
        Log::warning("[GenericDriver:{$this->carrier}] checkLineStatus non implementato per questo carrier.");
        return new LineStatusResult(
            success: false,
            result: 'KO',
            ontOperationalState: null,
            attenuation: null,
            opticalDistance: null,
            ontLanStatus: null,
            errorCode: 'NOT_IMPLEMENTED',
            errorDescription: "Line test non supportato per carrier {$this->carrier}",
        );
    }

    public function openTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->mock('openTroubleTicket', $ticket->codiceOrdineOlo);
    }

    public function updateTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->mock('updateTroubleTicket', $ticket->codiceOrdineOlo);
    }

    public function closeTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->mock('closeTroubleTicket', $ticket->codiceOrdineOlo);
    }

    public function handleInboundWebhook(Request $request): WebhookResult
    {
        return new WebhookResult(
            parsed: false,
            messageType: 'GENERIC_WEBHOOK',
            codiceOrdineOlo: null,
            codiceOrdineOf: null,
            newState: null,
            scheduledDate: null,
            cvlan: null,
            gponAttestazione: null,
            idApparatoConsegnato: null,
            flagDesospensione: null,
            errorMessage: "Webhook non implementato per carrier {$this->carrier}",
        );
    }

    private function mock(string $method, string $ref): CarrierResponse
    {
        Log::info("[GenericDriver:{$this->carrier}] {$method} ref={$ref} (mock)");
        return new CarrierResponse(
            success: true,
            carrierId: strtoupper($this->carrier) . '-MOCK-' . substr(md5($ref), 0, 8),
            rawPayload: null,
            httpStatus: 200,
        );
    }
}
