<?php
namespace Modules\Billing\CustomerBalance\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\CustomerBalance\Services\CustomerBalanceService;
use Modules\Billing\CustomerBalance\Http\Requests\SetOpeningBalanceRequest;
use Modules\Billing\CustomerBalance\Http\Requests\AdjustBalanceRequest;
use Brick\Money\Money;
use Carbon\Carbon;
class BalanceController extends ApiController {
    public function __construct(private CustomerBalanceService $service) {}
    public function show(string $customerId): JsonResponse {
        $customer = DB::table('customers')->findOrFail($customerId);
        return response()->json(['balance_cents'=>$customer->balance_amount,'balance_formatted'=>$this->service->getBalance($customer)->formatTo('it_IT'),'currency'=>$customer->balance_currency]);
    }
    public function statement(Request $request, string $customerId): JsonResponse {
        $customer = DB::table('customers')->findOrFail($customerId);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()));
        $to   = Carbon::parse($request->input('to', now()->endOfMonth()));
        return response()->json($this->service->getStatement($customer, $from, $to));
    }
    public function setOpening(SetOpeningBalanceRequest $request, string $customerId): JsonResponse {
        $customer = DB::table('customers')->findOrFail($customerId);
        $data = $request->validated();
        $this->service->setOpeningBalance($customer, Money::ofMinor($data['amount_cents'], 'EUR'), Carbon::parse($data['date']), $data['note']);
        return response()->json(['message' => 'Opening balance set']);
    }
    public function adjust(AdjustBalanceRequest $request, string $customerId): JsonResponse {
        $customer = DB::table('customers')->findOrFail($customerId);
        $data = $request->validated();
        $this->service->manualAdjustment($customer, $data['amount_cents'], $data['description']);
        return response()->json(['message' => 'Balance adjusted']);
    }
}
