<?php

declare(strict_types=1);

namespace Modules\Provisioning\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Jobs\ProcessCarrierWebhookJob;
use Modules\Provisioning\Services\Drivers\OpenFiberDriver;

/**
 * Riceve webhook push XML da Open Fiber (SPECINT v2.0/2.3).
 *
 * Pattern:
 * 1. Valida IP (middleware CarrierIpWhitelist)
 * 2. Valida Content-Type e XML ben formato
 * 3. Risponde ACK sincrono HTTP 200 (ENTRO il timeout OF)
 * 4. Dispatch ProcessCarrierWebhookJob (processing asincrono)
 *
 * CRITICO: OF si aspetta ACK entro ~5 secondi.
 * NON fare elaborazione sincrona in questo controller.
 */
class OpenFiberWebhookController extends Controller
{
    public function __construct(
        private readonly OpenFiberDriver $driver,
    ) {}

    public function handle(Request $request): Response
    {
        $body = $request->getContent();

        // Validazione minima: deve essere XML
        if (empty($body) || !str_contains($body, '<')) {
            Log::error('OF Webhook: body vuoto o non XML', ['ip' => $request->ip()]);
            return response('Bad Request', 400);
        }

        // Parse rapido per estrarre CODICE_ORDINE_OLO (per il log)
        $codiceOlo = $this->extractCodiceOlo($body);

        Log::info("OF Webhook ricevuto", [
            'ip'             => $request->ip(),
            'codice_olo'     => $codiceOlo,
            'content_length' => strlen($body),
        ]);

        // Dispatch job ASINCRONO — non bloccare questa risposta
        ProcessCarrierWebhookJob::dispatch(
            carrier: 'openfiber',
            payload: $body,
            sourceIp: $request->ip(),
        )->onQueue('webhooks');

        // ACK sincrono HTTP 200 — OBBLIGATORIO per OF
        return response('<ACK>OK</ACK>', 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function extractCodiceOlo(string $xml): ?string
    {
        if (preg_match('/<CODICE_ORDINE_OLO>(.*?)<\/CODICE_ORDINE_OLO>/s', $xml, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
