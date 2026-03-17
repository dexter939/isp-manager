<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maintenance\Http\Requests\AssignHardwareAssetRequest;
use Modules\Maintenance\Http\Requests\RegisterHardwareAssetRequest;
use Modules\Maintenance\Http\Requests\ScanQrRequest;
use Modules\Maintenance\Http\Resources\HardwareAssetResource;
use Modules\Maintenance\Models\HardwareAsset;
use Modules\Maintenance\Services\HardwareAssetService;
use Modules\Contracts\Models\Contract;

class HardwareAssetController extends ApiController
{
    public function __construct(
        private readonly HardwareAssetService $service,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $assets = HardwareAsset::where('tenant_id', auth()->user()->tenant_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type,   fn($q) => $q->where('type',   $request->type))
            ->with('contract.customer')
            ->paginate(25);

        return response()->json(HardwareAssetResource::collection($assets));
    }

    public function show(HardwareAsset $asset): JsonResponse
    {
        $this->authorizeTenant($asset);

        return response()->json([
            'data' => new HardwareAssetResource($asset->load('contract.customer')),
        ]);
    }

    public function register(RegisterHardwareAssetRequest $request): JsonResponse
    {
        $data = $request->validated();

        $asset = $this->service->register(
            tenantId:     auth()->user()->tenant_id,
            type:         $data['type'],
            serialNumber: $data['serial_number'],
            attributes:   $data,
        );

        return response()->json(['data' => new HardwareAssetResource($asset)], 201);
    }

    public function assign(AssignHardwareAssetRequest $request, HardwareAsset $asset): JsonResponse
    {
        $this->authorizeTenant($asset);

        $data = $request->validated();

        $contract = Contract::findOrFail($data['contract_id']);

        $this->service->assign($asset, $contract, auth()->id());

        return response()->json(['data' => new HardwareAssetResource($asset->fresh())]);
    }

    public function return(HardwareAsset $asset): JsonResponse
    {
        $this->authorizeTenant($asset);

        $this->service->return($asset, auth()->id());

        return response()->json(['data' => new HardwareAssetResource($asset->fresh())]);
    }

    public function scanQr(ScanQrRequest $request): JsonResponse
    {
        $data = $request->validated();

        $asset = $this->service->scanQr($data['qr_code']);

        $this->authorizeTenant($asset);

        return response()->json(['data' => new HardwareAssetResource($asset->load('contract.customer'))]);
    }

    public function unreturned(Request $request): JsonResponse
    {
        $afterDays = (int) $request->input('after_days', 30);

        $assets = $this->service->checkUnreturnedItems(
            tenantId:  auth()->user()->tenant_id,
            afterDays: $afterDays,
        );

        return response()->json(['data' => HardwareAssetResource::collection($assets)]);
    }

    public function stockSummary(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->stockSummary(auth()->user()->tenant_id),
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function authorizeTenant(HardwareAsset $asset): void
    {
        if ($asset->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
