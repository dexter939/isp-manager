<?php

declare(strict_types=1);

namespace Modules\AI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\ApiController;
use Modules\AI\Http\Requests\SendWhatsAppRequest;
use Modules\AI\Http\Requests\SendTemplateRequest;
use Modules\AI\Services\WhatsAppService;

class WhatsAppController extends ApiController
{
    public function __construct(
        private readonly WhatsAppService $whatsapp,
    ) {}

    /**
     * Verifica del webhook Meta (GET) — ritorna hub.challenge.
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Riceve eventi Meta (POST) — messaggi inbound e status updates.
     * No auth Sanctum — verificato con HMAC X-Hub-Signature-256.
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256', '');

        if (!$this->whatsapp->verifyWebhookSignature($request->getContent(), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $tenantId = (int) config('services.whatsapp.default_tenant_id', 1);

        $this->whatsapp->processInbound($request->all(), $tenantId);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Invia un messaggio di testo (autenticato).
     */
    public function send(SendWhatsAppRequest $request): JsonResponse
    {
        $this->middleware('auth:sanctum');

        $data = $request->validated();

        $message = $this->whatsapp->sendText(
            tenantId:   auth()->user()->tenant_id,
            toNumber:   $data['to'],
            body:       $data['body'],
            customerId: $data['customer_id'] ?? null,
        );

        return response()->json(['data' => $message], 201);
    }

    /**
     * Invia un template approvato da Meta.
     */
    public function sendTemplate(SendTemplateRequest $request): JsonResponse
    {
        $this->middleware('auth:sanctum');

        $data = $request->validated();

        $message = $this->whatsapp->sendTemplate(
            tenantId:     auth()->user()->tenant_id,
            toNumber:     $data['to'],
            templateName: $data['template_name'],
            languageCode: $data['language_code'] ?? 'it',
            components:   $data['components'] ?? [],
        );

        return response()->json(['data' => $message], 201);
    }
}
