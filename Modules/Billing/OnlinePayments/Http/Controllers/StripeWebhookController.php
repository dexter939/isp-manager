<?php

namespace Modules\Billing\OnlinePayments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Billing\OnlinePayments\Services\StripeGateway;
use Stripe\StripeClient;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $gateway = new StripeGateway(new StripeClient(config('online_payments.stripe.secret_key', '')));

        try {
            $gateway->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature', '')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        return response()->json(['message' => 'OK']);
    }
}
