<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services\Drivers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Contracts\CarrierInterface;
use Modules\Provisioning\Data\CarrierResponse;
use Modules\Provisioning\Data\LineStatusResult;
use Modules\Provisioning\Data\TroubleTicketRequest;
use Modules\Provisioning\Data\WebhookResult;
use Modules\Provisioning\Models\CarrierOrder;
use Modules\Provisioning\Services\XmlBuilder\OpenFiberXmlBuilder;
use Modules\Provisioning\Services\XmlParser\OpenFiberXmlParser;

/**
 * Driver Open Fiber.
 *
 * Delivery: SOAP/XML (SPECINT v2.0/2.3)
 * Line Testing: REST v2.3 — Header: lt-api-key (NON tokenID come v2.2)
 *
 * In CARRIER_MOCK=true: logga e simula risposta OK.
 */
class OpenFiberDriver implements CarrierInterface
{
    private bool $isMocked;
    private Client $http;
    private string $ltApiKey;
    private string $oltCode;  // SourceSystem = codice OLO assegnato da OF

    public function __construct(
        private readonly OpenFiberXmlBuilder $xmlBuilder,
        private readonly OpenFiberXmlParser  $xmlParser,
    ) {
        $this->isMocked = (bool) config('app.carrier_mock', false);
        $this->ltApiKey = (string) config('provisioning.openfiber.lt_api_key', '');
        $this->oltCode  = (string) config('provisioning.openfiber.codice_operatore', '');

        $this->http = new Client([
            'base_uri' => config('provisioning.openfiber.rest_endpoint', ''),
            'timeout'  => 30,
            'headers'  => [
                'lt-api-key' => $this->ltApiKey, // v2.3: NON tokenID
                'Accept'     => 'application/json',
            ],
        ]);
    }

    public function sendActivationOrder(CarrierOrder $order): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildActivationSetup($order);
        return $this->sendSoap('OLO_ActivationSetup_OpenStream', $xml, $order->codice_ordine_olo);
    }

    public function sendChangeOrder(CarrierOrder $order): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildChangeSetup($order);
        return $this->sendSoap('OLO_ChangeSetup_OpenStream', $xml, $order->codice_ordine_olo);
    }

    public function sendDeactivationOrder(CarrierOrder $order): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildDeactivation($order);
        return $this->sendSoap('OLO_DeactivationOrder', $xml, $order->codice_ordine_olo);
    }

    public function sendReschedule(CarrierOrder $order, Carbon $newDate): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildReschedule($order, $newDate);
        return $this->sendSoap('OLO_Reschedule', $xml, $order->codice_ordine_olo);
    }

    public function sendUnsuspend(CarrierOrder $order): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildUnsuspend($order);
        return $this->sendSoap('OLO_StatusUpdate', $xml, $order->codice_ordine_olo);
    }

    /**
     * Line Testing API v2.3.
     * GET /linetesting?ResourceId={CORD_o_UI}&SourceSystem={OLO_CODE}
     * Header: lt-api-key (NON tokenID — BREAKING CHANGE v2.3)
     *
     * Codici errore v2.3:
     * L01 → retry (service not available)
     * L02 → retry se timeout, ticket se unreachable (NOVITÀ v2.3)
     * L03 → bug (bad request), non ritentare
     * L04 → codice UI non trovato, verificare DB copertura
     * L05 → quota exceeded → HTTP 427 (gestito da ApiQuotaManager)
     * L06 → unauthorized (lt-api-key invalido)
     * L07 → MSO — massive fault, NON aprire ticket singolo
     */
    public function checkLineStatus(string $resourceId): LineStatusResult
    {
        if ($this->isMocked) {
            Log::info("[MOCK OF] Line test resourceId={$resourceId}");
            return LineStatusResult::fromOfV23Response([
                'TestInstanceId'     => 9999,
                'Result'             => 'OK',
                'OntOperationalState'=> 'UP',
                'Attenuation'        => '-12.5',
                'OpticalDistance'    => '850.0',
                'OntLanStatus'       => 'ENABLED',
            ]);
        }

        $response = $this->http->get('/linetesting', [
            'query' => [
                'ResourceId'   => $resourceId,
                'SourceSystem' => $this->oltCode,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true) ?? [];
        return LineStatusResult::fromOfV23Response($data);
    }

    public function openTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildTicketRequest($ticket);
        return $this->sendSoap('OLO_TicketRequest', $xml, $ticket->codiceOrdineOlo);
    }

    public function updateTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildTicketUpdate($ticket);
        return $this->sendSoap('OLO_TicketUpdate', $xml, $ticket->codiceOrdineOlo);
    }

    public function closeTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        $xml = $this->xmlBuilder->buildTicketCompletion($ticket);
        return $this->sendSoap('OLO_TicketCompletion', $xml, $ticket->codiceOrdineOlo);
    }

    public function handleInboundWebhook(Request $request): WebhookResult
    {
        $body = $request->getContent();

        // Estrai body SOAP dall'envelope
        $bodyXml = $this->extractSoapBody($body);

        return $this->xmlParser->parse($bodyXml);
    }

    // ---- Private helpers ----

    private function sendSoap(string $action, string $xml, string $codiceOlo): CarrierResponse
    {
        if ($this->isMocked) {
            Log::info("[MOCK OF] SOAP {$action} codice_olo={$codiceOlo}");
            return new CarrierResponse(
                success: true,
                carrierId: 'OF-MOCK-' . strtoupper(substr(md5($codiceOlo), 0, 8)),
                rawPayload: "<mock>{$action}</mock>",
                httpStatus: 200,
            );
        }

        $soapEndpoint = config('provisioning.openfiber.soap_endpoint', '');
        $certPath     = config('provisioning.openfiber.client_cert_path', '');

        $soapClient = new \SoapClient($soapEndpoint . '?wsdl', [
            'local_cert' => $certPath,
            'trace'      => true,
            'exceptions' => true,
            'encoding'   => 'UTF-8',
        ]);

        try {
            $response   = $soapClient->__doRequest($xml, $soapEndpoint, $action, SOAP_1_1);
            $ofOrderId  = $this->extractOfOrderId($response);

            return new CarrierResponse(
                success: true,
                carrierId: $ofOrderId,
                rawPayload: $response,
                httpStatus: 200,
            );
        } catch (\SoapFault $e) {
            return new CarrierResponse(
                success: false,
                carrierId: '',
                rawPayload: $soapClient->__getLastResponse() ?? '',
                httpStatus: 500,
                errorCode: $e->faultcode ?? 'SOAP_FAULT',
                errorMessage: $e->getMessage(),
            );
        }
    }

    /** Estrae CODICE_ORDINE_OF dalla risposta SOAP */
    private function extractOfOrderId(string $xmlResponse): string
    {
        if (preg_match('/<CODICE_ORDINE_OF>(.*?)<\/CODICE_ORDINE_OF>/s', $xmlResponse, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    /** Estrae il Body dall'envelope SOAP */
    private function extractSoapBody(string $envelope): string
    {
        if (preg_match('/<(?:soapenv|soap):Body[^>]*>(.*?)<\/(?:soapenv|soap):Body>/s', $envelope, $m)) {
            return trim($m[1]);
        }
        return $envelope; // fallback: l'intero envelope
    }
}
