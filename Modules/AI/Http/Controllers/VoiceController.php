<?php

declare(strict_types=1);

namespace Modules\AI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AI\Services\VoiceService;

class VoiceController extends Controller
{
    public function __construct(
        private readonly VoiceService $voice,
    ) {}

    /**
     * Webhook Twilio — chiamata inbound.
     * Risponde con TwiML (XML).
     * No Sanctum auth — verificato tramite X-Twilio-Signature.
     */
    public function inbound(Request $request): Response
    {
        $tenantId = (int) config('services.twilio.default_tenant_id', 1);

        $twiml = $this->voice->handleInbound($request->all(), $tenantId);

        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Webhook Twilio — SMS inbound.
     */
    public function inboundSms(Request $request): Response
    {
        $tenantId = (int) config('services.twilio.default_tenant_id', 1);

        $twiml = $this->voice->processInboundSms($request->all(), $tenantId);

        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }
}
