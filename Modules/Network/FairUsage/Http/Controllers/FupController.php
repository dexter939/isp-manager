<?php

namespace Modules\Network\FairUsage\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Network\FairUsage\Http\Requests\StoreTopupRequest;
use Modules\Network\FairUsage\Models\FupTopupProduct;
use Modules\Network\FairUsage\Services\TopupService;
use Modules\Network\FairUsage\Http\Resources\FupTopupProductResource;

class FupController extends ApiController
{
    public function __construct(private TopupService $topupService) {}

    public function usage(string $accountId): JsonResponse
    {
        $year  = (int) date('Y');
        $month = (int) date('n');
        $usage = DB::table('customer_traffic_usage')->where('pppoe_account_id', $accountId)->where('period_year', $year)->where('period_month', $month)->first();
        if (!$usage) return response()->json(['pppoe_account_id' => $accountId, 'bytes_total' => 0, 'cap_gb' => null, 'fup_triggered' => false, 'usage_percent' => 0]);
        $totalCap = (($usage->cap_gb ?? 0) + ($usage->topup_gb_added ?? 0));
        return response()->json(['pppoe_account_id' => $accountId, 'bytes_download' => $usage->bytes_download, 'bytes_upload' => $usage->bytes_upload, 'bytes_total' => $usage->bytes_total, 'cap_gb' => $usage->cap_gb, 'topup_gb_added' => $usage->topup_gb_added, 'total_cap_gb' => $totalCap, 'fup_triggered' => (bool) $usage->fup_triggered, 'fup_triggered_at' => $usage->fup_triggered_at, 'usage_percent' => $totalCap > 0 ? round($usage->bytes_total / ($totalCap * 1073741824) * 100, 2) : 0]);
    }

    public function products(): JsonResponse
    {
        return response()->json(FupTopupProductResource::collection(FupTopupProduct::where('is_active', true)->orderBy('gb_amount')->get()));
    }

    public function topup(StoreTopupRequest $request): JsonResponse
    {
        $data    = $request->validated();
        $account = DB::table('pppoe_accounts')->where('id', $data['pppoe_account_id'])->firstOrFail();
        $product = FupTopupProduct::where('id', $data['product_id'])->where('is_active', true)->firstOrFail();
        $purchase = $this->topupService->purchase($account, $product, $data['payment_method']);
        return response()->json($purchase, 201);
    }
}
