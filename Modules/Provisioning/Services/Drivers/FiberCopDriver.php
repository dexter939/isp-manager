<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services\Drivers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Contracts\CarrierInterface;
use Modules\Provisioning\Data\CarrierResponse;
use Modules\Provisioning\Data\LineStatusResult;
use Modules\Provisioning\Data\TroubleTicketRequest;
use Modules\Provisioning\Data\WebhookResult;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Models\CarrierOrder;

/**
 * Driver FiberCop NGASP (API v1.2, Agosto 2024).
 *
 * Autenticazione: OAuth 2.0 client credentials (RFC 6749).
 * Token scaduto (HTTP 401) → rinnova automaticamente.
 * client_key + client_secret da zip cifrato + SMS.
 *
 * In CARRIER_MOCK=true: logga e simula risposta OK.
 */
class FiberCopDriver implements CarrierInterface
{
    private const TOKEN_CACHE_KEY = 'fibercop_oauth_token';

    private bool $isMocked;
    private Client $http;
    private string $clientKey;
    private string $clientSecret;
    private string $tokenEndpoint;
    private string $apiBaseUrl;

    public function __construct()
    {
        $this->isMocked      = (bool) config('app.carrier_mock', false);
        $this->clientKey     = (string) config('provisioning.fibercop.client_key', '');
        $this->clientSecret  = (string) config('provisioning.fibercop.client_secret', '');
        $this->tokenEndpoint = (string) config('provisioning.fibercop.token_endpoint', '');
        $this->apiBaseUrl    = (string) config('provisioning.fibercop.api_base_url', '');

        $this->http = new Client([
            'base_uri' => $this->apiBaseUrl,
            'timeout'  => 30,
        ]);
    }

    public function sendActivationOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->callApi('POST', '/orders/activation', [
            'codice_ordine_olo' => $order->codice_ordine_olo,
            'codice_ui'         => $order->contract->codice_ui,
            'cvlan'             => $order->cvlan,
            'cliente'           => [
                'nome'          => $order->contract->customer->full_name,
                'telefono'      => $order->contract->customer->cellulare,
                'email'         => $order->contract->customer->email,
            ],
        ], $order->codice_ordine_olo);
    }

    public function sendChangeOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->callApi('POST', "/orders/{$order->codice_ordine_of}/change", [
            'codice_ordine_olo' => $order->codice_ordine_olo,
        ], $order->codice_ordine_olo);
    }

    public function sendDeactivationOrder(CarrierOrder $order): CarrierResponse
    {
        return $this->callApi('POST', "/orders/{$order->codice_ordine_of}/deactivate", [
            'codice_ordine_olo' => $order->codice_ordine_olo,
            'motivo'            => 'RECESSO_CLIENTE',
        ], $order->codice_ordine_olo);
    }

    public function sendReschedule(CarrierOrder $order, Carbon $newDate): CarrierResponse
    {
        return $this->callApi('PUT', "/orders/{$order->codice_ordine_of}/reschedule", [
            'nuova_data' => $newDate->format('Y-m-d'),
        ], $order->codice_ordine_olo);
    }

    public function sendUnsuspend(CarrierOrder $order): CarrierResponse
    {
        return $this->callApi('POST', "/orders/{$order->codice_ordine_of}/unsuspend", [], $order->codice_ordine_olo);
    }

    /**
     * FiberCop NGASP: statusZpoint (stato ONT lato cliente).
     * Differisce da OF (non è REST /linetesting ma endpoint specifico).
     */
    public function checkLineStatus(string $resourceId): LineStatusResult
    {
        if ($this->isMocked) {
            Log::info("[MOCK FC] statusZpoint codice_ui={$resourceId}");
            return new LineStatusResult(
                success: true,
                result: 'OK',
                ontOperationalState: 'UP',
                attenuation: null,
                opticalDistance: null,
                ontLanStatus: 'ENABLED',
            );
        }

        $response = $this->callApi('GET', "/ngasp/statusZpoint?codice_ui={$resourceId}", [], $resourceId);

        return new LineStatusResult(
            success: $response->success,
            result: $response->success ? 'OK' : 'KO',
            ontOperationalState: null,
            attenuation: null,
            opticalDistance: null,
            ontLanStatus: null,
            errorCode: $response->errorCode,
            errorDescription: $response->errorMessage,
        );
    }

    public function openTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->callApi('POST', '/tickets', [
            'codice_ordine_olo'  => $ticket->codiceOrdineOlo,
            'codice_ordine_fc'   => $ticket->codiceOrdineOf,
            'telefono_cliente'   => $ticket->recapitoTelefonicoCliente,
            'causa'              => $ticket->causaGuasto,
            'descrizione'        => $ticket->descTecnicaGuasto,
        ], $ticket->codiceOrdineOlo);
    }

    public function updateTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->callApi('PUT', "/tickets/{$ticket->ticketId}", [
            'causa'       => $ticket->causaGuasto,
            'descrizione' => $ticket->descTecnicaGuasto,
        ], $ticket->codiceOrdineOlo);
    }

    public function closeTroubleTicket(TroubleTicketRequest $ticket): CarrierResponse
    {
        return $this->callApi('POST', "/tickets/{$ticket->ticketId}/close", [], $ticket->codiceOrdineOlo);
    }

    public function handleInboundWebhook(Request $request): WebhookResult
    {
        $data = $request->json()->all();

        // FC usa JSON (non SOAP come OF)
        $stato      = $data['stato_ordine'] ?? $data['state'] ?? '';
        $codiceOlo  = $data['codice_ordine_olo'] ?? null;
        $codiceOf   = $data['codice_ordine_fc'] ?? null;

        $newState = null;
        if ($stato) {
            try {
                $newState = OrderState::fromOfStatusCode($stato);
            } catch (\UnexpectedValueException) {}
        }

        return new WebhookResult(
            parsed: !empty($codiceOlo),
            messageType: 'FC_StatusUpdate',
            codiceOrdineOlo: $codiceOlo,
            codiceOrdineOf: $codiceOf,
            newState: $newState,
            scheduledDate: $data['data_appuntamento'] ?? null,
            cvlan: $data['cvlan'] ?? null,
            gponAttestazione: null,
            idApparatoConsegnato: $data['id_apparato'] ?? null,
            flagDesospensione: null,
            rawFields: $data,
        );
    }

    // ---- OAuth 2.0 ----

    /**
     * Ottiene o rinnova il Bearer token OAuth2 (client credentials).
     * Token in cache Redis fino a scadenza - 60s.
     */
    private function getBearerToken(): string
    {
        if ($this->isMocked) {
            return 'mock-bearer-token';
        }

        return Cache::remember(self::TOKEN_CACHE_KEY, 3540, function () {
            $response = (new Client())->post($this->tokenEndpoint, [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientKey,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $ttl  = ($data['expires_in'] ?? 3600) - 60;
            Cache::put(self::TOKEN_CACHE_KEY, $data['access_token'], $ttl);

            return $data['access_token'];
        });
    }

    /**
     * Esegue chiamata REST con Bearer token.
     * Se HTTP 401: invalida cache token e ritenta una volta.
     */
    private function callApi(string $method, string $path, array $payload, string $codiceOlo): CarrierResponse
    {
        if ($this->isMocked) {
            Log::info("[MOCK FC] {$method} {$path} codice_olo={$codiceOlo}");
            return new CarrierResponse(
                success: true,
                carrierId: 'FC-MOCK-' . strtoupper(substr(md5($codiceOlo), 0, 8)),
                rawPayload: json_encode(['mock' => true, 'path' => $path]),
                httpStatus: 200,
            );
        }

        $doRequest = function () use ($method, $path, $payload): \Psr\Http\Message\ResponseInterface {
            $options = [
                'headers' => ['Authorization' => 'Bearer ' . $this->getBearerToken()],
            ];
            if (!empty($payload) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $payload;
            }
            return $this->http->request($method, $path, $options);
        };

        try {
            $response = $doRequest();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                // Token scaduto → invalida cache e riprova
                Cache::forget(self::TOKEN_CACHE_KEY);
                try {
                    $response = $doRequest();
                } catch (\Throwable $e2) {
                    return new CarrierResponse(
                        success: false,
                        carrierId: '',
                        rawPayload: $e2->getMessage(),
                        httpStatus: 401,
                        errorCode: 'UNAUTHORIZED',
                        errorMessage: $e2->getMessage(),
                    );
                }
            } else {
                $body = (string) $e->getResponse()->getBody();
                return new CarrierResponse(
                    success: false,
                    carrierId: '',
                    rawPayload: $body,
                    httpStatus: $e->getResponse()->getStatusCode(),
                    errorMessage: $e->getMessage(),
                );
            }
        }

        $body = (string) $response->getBody();
        $data = json_decode($body, true) ?? [];

        return new CarrierResponse(
            success: $response->getStatusCode() < 400,
            carrierId: $data['codice_ordine_fc'] ?? $data['id'] ?? '',
            rawPayload: $body,
            httpStatus: $response->getStatusCode(),
        );
    }
}
