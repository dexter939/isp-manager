<?php

namespace Modules\Maintenance\InventoryRma\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maintenance\InventoryRma\Http\Requests\DeployItemRequest;
use Modules\Maintenance\InventoryRma\Http\Requests\OpenRmaRequest;
use Modules\Maintenance\InventoryRma\Http\Requests\ResolveRmaRequest;
use Modules\Maintenance\InventoryRma\Models\RmaRequest;
use Modules\Maintenance\InventoryRma\Services\InventoryLifecycleService;
use Modules\Maintenance\InventoryRma\Services\InventoryReportService;

class InventoryRmaController extends ApiController
{
    public function __construct(
        private InventoryLifecycleService $lifecycle,
        private InventoryReportService $reports,
    ) {}

    public function deploy(DeployItemRequest $request, string $itemId): JsonResponse
    {
        $data = $request->validated();
        $this->lifecycle->deploy($itemId, $data['customer_id'], $data['contract_id'], $data['technician_id']);
        return response()->json(['message' => 'Item deployed']);
    }

    public function openRma(OpenRmaRequest $request, string $itemId): JsonResponse
    {
        $data = $request->validated();
        $rma  = $this->lifecycle->openRma($itemId, $data['reason'], $data['description'], $data['supplier_id'] ?? null);
        return response()->json($rma, 201);
    }

    public function resolveRma(ResolveRmaRequest $request, RmaRequest $rma): JsonResponse
    {
        $data = $request->validated();
        $this->lifecycle->resolveRma($rma, $data['resolution'], $data['replacement_item_id'] ?? null);
        return response()->json(['message' => 'RMA resolved']);
    }

    public function listRma(Request $request): JsonResponse
    {
        $rmas = RmaRequest::when($request->input('supplier_id'), fn($q, $s) => $q->where('supplier_id', $s))
            ->when($request->input('open_only'), fn($q) => $q->whereNull('resolved_at'))
            ->orderByDesc('created_at')
            ->paginate(25);
        return response()->json($rmas);
    }

    public function defectReport(): JsonResponse
    {
        return response()->json($this->reports->getDefectRateByModel());
    }

    public function rmaReport(): JsonResponse
    {
        return response()->json($this->reports->getRmaStatusReport());
    }

    public function stockLevels(): JsonResponse
    {
        return response()->json($this->reports->getStockLevels());
    }
}
