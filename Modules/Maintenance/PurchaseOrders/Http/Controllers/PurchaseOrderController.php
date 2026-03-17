<?php

namespace Modules\Maintenance\PurchaseOrders\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Maintenance\PurchaseOrders\Http\Requests\ReceivePurchaseOrderRequest;
use Modules\Maintenance\PurchaseOrders\Http\Requests\StorePurchaseOrderRequest;
use Modules\Maintenance\PurchaseOrders\Http\Requests\StoreReorderRuleRequest;
use Modules\Maintenance\PurchaseOrders\Models\PurchaseOrder;
use Modules\Maintenance\PurchaseOrders\Models\ReorderRule;
use Modules\Maintenance\PurchaseOrders\Models\Supplier;
use Modules\Maintenance\PurchaseOrders\Services\PurchaseOrderService;
use Modules\Maintenance\PurchaseOrders\Http\Resources\PurchaseOrderResource;
use Modules\Maintenance\PurchaseOrders\Http\Resources\SupplierResource;

class PurchaseOrderController extends ApiController
{
    public function __construct(private PurchaseOrderService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(PurchaseOrderResource::collection(PurchaseOrder::with(['supplier', 'items'])->paginate(20)));
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $po   = $this->service->create($data);
        return response()->json(new PurchaseOrderResource($po->load(['supplier', 'items'])), 201);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items'])));
    }

    public function send(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(new PurchaseOrderResource($this->service->send($purchaseOrder)->load(['supplier', 'items'])));
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $data     = $request->validated();
        $received = collect($data['items'])->mapWithKeys(fn($i) => [$i['id'] => $i['qty']])->all();
        return response()->json(new PurchaseOrderResource($this->service->receive($purchaseOrder, $received)->load(['supplier', 'items'])));
    }

    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(new PurchaseOrderResource($this->service->cancel($purchaseOrder)->load(['supplier', 'items'])));
    }

    // Reorder rules
    public function reorderRules(): JsonResponse
    {
        return response()->json(ReorderRule::with('supplier')->get());
    }

    public function storeReorderRule(StoreReorderRuleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rule = ReorderRule::updateOrCreate(
            ['inventory_model_id' => $data['inventory_model_id']],
            $data
        );
        return response()->json($rule, 201);
    }

    public function suppliers(): JsonResponse
    {
        return response()->json(SupplierResource::collection(Supplier::all()));
    }
}
