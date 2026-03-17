<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maintenance\Http\Requests\AdjustInventoryRequest;
use Modules\Maintenance\Http\Requests\ConsumeInventoryRequest;
use Modules\Maintenance\Http\Requests\ReceiveInventoryRequest;
use Modules\Maintenance\Http\Requests\StoreInventoryItemRequest;
use Modules\Maintenance\Http\Resources\InventoryItemResource;
use Modules\Maintenance\Models\InventoryItem;
use Modules\Maintenance\Services\InventoryService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryController extends ApiController
{
    public function __construct(
        private readonly InventoryService $service,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(InventoryItem::class)
            ->allowedFilters([
                AllowedFilter::exact('category'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::scope('low_stock'),
            ])
            ->allowedSorts(['name', 'quantity', 'category', 'sku'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->paginate(50);

        return response()->json(InventoryItemResource::collection($items));
    }

    public function show(InventoryItem $item): JsonResponse
    {
        return response()->json([
            'data' => new InventoryItemResource($item->load(['movements' => fn($q) => $q->orderByDesc('moved_at')->limit(20)])),
        ]);
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $data = $request->validated();

        $item = InventoryItem::create([
            ...$data,
            'tenant_id' => auth()->user()->tenant_id,
            'quantity'  => 0,
        ]);

        return response()->json(['data' => new InventoryItemResource($item)], 201);
    }

    public function receive(ReceiveInventoryRequest $request, InventoryItem $item): JsonResponse
    {
        $data = $request->validated();

        $movement = $this->service->receive(
            item:      $item,
            quantity:  $data['quantity'],
            userId:    auth()->id(),
            reference: $data['reference'] ?? '',
            notes:     $data['notes'] ?? '',
        );

        return response()->json(['data' => $movement], 201);
    }

    public function consume(ConsumeInventoryRequest $request, InventoryItem $item): JsonResponse
    {
        $data = $request->validated();

        $movement = $this->service->consume(
            item:      $item,
            quantity:  $data['quantity'],
            userId:    auth()->id(),
            ticketId:  $data['ticket_id'] ?? null,
            reference: $data['reference'] ?? '',
            notes:     $data['notes'] ?? '',
        );

        return response()->json(['data' => $movement], 201);
    }

    public function adjust(AdjustInventoryRequest $request, InventoryItem $item): JsonResponse
    {
        $data = $request->validated();

        $movement = $this->service->adjust(
            item:        $item,
            newQuantity: $data['quantity'],
            userId:      auth()->id(),
            notes:       $data['notes'] ?? 'Rettifica inventario',
        );

        return response()->json(['data' => $movement]);
    }

    public function lowStock(): JsonResponse
    {
        $items = $this->service->getLowStock(auth()->user()->tenant_id);
        return response()->json(['data' => InventoryItemResource::collection($items)]);
    }
}
