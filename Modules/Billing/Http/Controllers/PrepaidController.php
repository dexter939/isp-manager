<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Billing\Http\Requests\CreateWalletRequest;
use Modules\Billing\Http\Requests\PrepaidTopupRequest;
use Modules\Billing\Models\PrepaidReseller;
use Modules\Billing\Models\PrepaidTopupOrder;
use Modules\Billing\Models\PrepaidTopupProduct;
use Modules\Billing\Models\PrepaidWallet;
use Modules\Billing\Enums\PrepaidOrderStatus;
use Modules\Billing\Services\PayPalPaymentService;
use Modules\Billing\Services\PrepaidWalletService;
use Modules\Billing\Services\ResellerService;

class PrepaidController extends Controller
{
    public function __construct(
        private readonly PrepaidWalletService $walletService,
        private readonly ResellerService      $resellerService,
        private readonly PayPalPaymentService $paypalService,
    ) {}

    /**
     * GET /v1/prepaid/wallets — paginated list for authenticated tenant.
     */
    public function wallets(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $wallets = PrepaidWallet::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($wallets);
    }

    /**
     * GET /v1/prepaid/wallets/{id} — wallet detail with balance and last 10 transactions.
     */
    public function walletShow(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $wallet = PrepaidWallet::where('tenant_id', $tenantId)
            ->findOrFail($id);

        $lastTransactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'wallet'       => $wallet,
            'balance'      => [
                'amount'   => $wallet->balance_amount,
                'currency' => $wallet->balance_currency,
                'formatted' => (string) $wallet->balance,
            ],
            'transactions' => $lastTransactions,
        ]);
    }

    /**
     * GET /v1/prepaid/wallets/{id}/transactions — paginated transactions.
     */
    public function transactions(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $wallet = PrepaidWallet::where('tenant_id', $tenantId)
            ->findOrFail($id);

        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($transactions);
    }

    /**
     * GET /v1/prepaid/products — active products ordered by sort_order.
     */
    public function products(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $products = PrepaidTopupProduct::where('tenant_id', $tenantId)
            ->active()
            ->ordered()
            ->get();

        return response()->json($products);
    }

    /**
     * POST /v1/prepaid/topup/initiate
     * Creates a PayPal order and a pending PrepaidTopupOrder.
     * Returns PayPal order_id.
     */
    public function initiateTopup(PrepaidTopupRequest $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $wallet  = PrepaidWallet::where('tenant_id', $tenantId)->findOrFail($request->wallet_id);
        $product = PrepaidTopupProduct::where('tenant_id', $tenantId)->findOrFail($request->product_id);

        $paypalOrderId = $this->paypalService->createOrder($product);

        $order = PrepaidTopupOrder::create([
            'tenant_id'         => $tenantId,
            'wallet_id'         => $wallet->id,
            'product_id'        => $product->id,
            'reseller_id'       => $request->reseller_id,
            'amount_amount'     => $product->amount_amount,
            'amount_currency'   => $product->amount_currency,
            'payment_method'    => $request->payment_method,
            'payment_reference' => $paypalOrderId,
            'status'            => PrepaidOrderStatus::Pending,
        ]);

        return response()->json([
            'order_id'       => $order->id,
            'paypal_order_id' => $paypalOrderId,
        ], 201);
    }

    /**
     * POST /v1/prepaid/topup/confirm
     * Captures PayPal order; if successful, tops up the wallet.
     */
    public function confirmTopup(Request $request): JsonResponse
    {
        $request->validate([
            'paypal_order_id' => ['required', 'string'],
            'order_id'        => ['required', 'uuid'],
        ]);

        $tenantId = $request->user()->tenant_id;

        $pendingOrder = PrepaidTopupOrder::where('tenant_id', $tenantId)
            ->where('status', PrepaidOrderStatus::Pending->value)
            ->findOrFail($request->order_id);

        $captured = $this->paypalService->captureOrder($request->paypal_order_id);

        if (! $captured) {
            $pendingOrder->update(['status' => PrepaidOrderStatus::Failed]);

            return response()->json(['message' => 'Pagamento PayPal non riuscito.'], 422);
        }

        $wallet  = PrepaidWallet::findOrFail($pendingOrder->wallet_id);
        $product = PrepaidTopupProduct::findOrFail($pendingOrder->product_id);

        $reseller = $pendingOrder->reseller_id
            ? PrepaidReseller::find($pendingOrder->reseller_id)
            : null;

        $transaction = $this->walletService->topup(
            wallet:           $wallet,
            product:          $product,
            paymentMethod:    $pendingOrder->payment_method->value,
            paymentReference: $request->paypal_order_id,
            reseller:         $reseller,
        );

        return response()->json([
            'transaction' => $transaction,
            'balance'     => [
                'amount'   => $wallet->fresh()->balance_amount,
                'currency' => $wallet->balance_currency,
            ],
        ]);
    }

    /**
     * GET /v1/prepaid/resellers/{id}/statement — last 30 days statement.
     */
    public function resellerStatement(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $reseller = PrepaidReseller::where('tenant_id', $tenantId)->findOrFail($id);

        $from      = Carbon::now()->subDays(30);
        $to        = Carbon::now();
        $statement = $this->resellerService->getStatement($reseller, $from, $to);

        return response()->json([
            'from'          => $from->toDateString(),
            'to'            => $to->toDateString(),
            'transactions'  => $statement['transactions'],
            'total_credits' => (string) $statement['total_credits'],
            'total_debits'  => (string) $statement['total_debits'],
        ]);
    }
}
