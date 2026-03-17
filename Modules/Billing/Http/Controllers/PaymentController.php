<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\InitiateStripeRequest;
use Modules\Billing\Http\Resources\PaymentResource;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\Payment;
use Modules\Billing\Services\StripeService;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Lista pagamenti per una fattura.
     */
    public function index(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        return response()->json([
            'data' => PaymentResource::collection($invoice->payments()->latest()->get()),
        ]);
    }

    /**
     * Avvia il pagamento Stripe per una fattura.
     */
    public function initiateStripe(InitiateStripeRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validated();

        $customer = $invoice->customer;
        $stripeCustomerId = $this->stripe->ensureStripeCustomer(
            $customer->id,
            $customer->email,
            $customer->full_name,
        );

        $payment = $this->stripe->chargeInvoice(
            $invoice,
            $stripeCustomerId,
            $validated['payment_method_id'],
        );

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }

    /**
     * Webhook Stripe (non autenticato via Sanctum — usa signature HMAC).
     */
    public function stripeWebhook(Request $request): JsonResponse
    {
        $result = $this->stripe->handleWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature', ''),
        );

        return response()->json($result);
    }
}
