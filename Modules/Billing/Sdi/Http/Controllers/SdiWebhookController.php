<?php

namespace Modules\Billing\Sdi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Billing\Sdi\Services\SdiTransmissionService;

class SdiWebhookController extends Controller
{
    public function __construct(private readonly SdiTransmissionService $service) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody  = $request->getContent();
        $received = $request->header('X-Aruba-Signature', '');
        $expected = hash_hmac('sha256', $rawBody, config('sdi.aruba_api_key', ''));

        if (!hash_equals($expected, $received)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payload          = $request->json()->all();
        $notificationCode = $payload['notification_type'] ?? '';
        $transmissionId   = $payload['transmission_id'] ?? null;

        if (!$transmissionId || !$notificationCode) {
            return response()->json(['message' => 'Missing fields'], 422);
        }

        $this->service->processNotification($notificationCode, $transmissionId, $rawBody);

        return response()->json(['message' => 'OK']);
    }
}
