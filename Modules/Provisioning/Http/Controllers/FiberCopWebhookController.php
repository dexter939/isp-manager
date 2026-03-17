<?php

declare(strict_types=1);

namespace Modules\Provisioning\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Jobs\ProcessCarrierWebhookJob;

/**
 * Riceve webhook push JSON da FiberCop NGASP.
 *
 * FC usa JSON (non SOAP come OF).
 * Stessa policy: ACK sincrono immediato + processing asincrono.
 */
class FiberCopWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        if (empty($data)) {
            return response()->json(['error' => 'Body vuoto'], 400);
        }

        $codiceOlo = $data['codice_ordine_olo'] ?? 'unknown';

        Log::info("FC Webhook ricevuto", [
            'ip'         => $request->ip(),
            'codice_olo' => $codiceOlo,
            'keys'       => array_keys($data),
        ]);

        ProcessCarrierWebhookJob::dispatch(
            carrier: 'fibercop',
            payload: $request->getContent(),
            sourceIp: $request->ip(),
        )->onQueue('webhooks');

        return response()->json(['status' => 'ACK']);
    }
}
