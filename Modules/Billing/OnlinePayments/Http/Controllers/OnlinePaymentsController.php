<?php

namespace Modules\Billing\OnlinePayments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\Models\Invoice;
use Modules\Billing\OnlinePayments\Models\OnlinePaymentMethod;
use Modules\Billing\OnlinePayments\Services\PaymentGatewayFactory;
use Modules\Billing\OnlinePayments\Http\Requests\ChargeRequest;

class OnlinePaymentsController extends ApiController
{
    public function __construct(private readonly PaymentGatewayFactory $factory) {}

    public function methods(Request $request): JsonResponse
    {
        $methods = OnlinePaymentMethod::where('customer_id', $request->user()->id)
            ->where('active', true)
            ->get();

        return response()->json(['data' => $methods]);
    }

    public function createLink(Request $request, int $invoiceId): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $gateway = $this->factory->make(config('online_payments.default_gateway', 'stripe'));
        $url     = $gateway->createPaymentLink($request->user(), $invoice);

        return response()->json(['data' => ['url' => $url]]);
    }

    public function setup(Request $request): JsonResponse
    {
        $gateway = $this->factory->make('stripe');
        $result  = $gateway->createRecurringSetup($request->user());

        return response()->json(['data' => $result]);
    }

    public function charge(ChargeRequest $request, int $methodId): JsonResponse
    {
        $validated = $request->validated();

        $method  = OnlinePaymentMethod::findOrFail($methodId);
        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $gateway = $this->factory->make($method->gateway);

        $transaction = $gateway->chargeRecurring($method, $invoice);

        return response()->json(['data' => $transaction]);
    }

    public function deactivateMethod(int $methodId): JsonResponse
    {
        $method = OnlinePaymentMethod::findOrFail($methodId);
        $method->update(['active' => false]);

        return response()->json(['message' => 'Metodo di pagamento disattivato.']);
    }
}
