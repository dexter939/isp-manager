<?php

declare(strict_types=1);

namespace Modules\Monitoring\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Monitoring\Models\NetworkAlert;
use Modules\Monitoring\Http\Resources\NetworkAlertResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NetworkAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NetworkAlert::class);

        $alerts = QueryBuilder::for(NetworkAlert::class)
            ->allowedFilters([
                AllowedFilter::exact('severity'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('source'),
            ])
            ->allowedSorts(['created_at', 'severity', 'status'])
            ->defaultSort('-created_at')
            ->with(['customer', 'contract'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->paginate($request->integer('per_page', 20));

        return response()->json(NetworkAlertResource::collection($alerts));
    }

    public function show(NetworkAlert $alert): JsonResponse
    {
        $this->authorize('view', $alert);
        $alert->load(['customer', 'contract', 'cpeDevice']);
        return response()->json(new NetworkAlertResource($alert));
    }

    public function acknowledge(NetworkAlert $alert): JsonResponse
    {
        $this->authorize('update', $alert);
        $alert->acknowledge(auth()->id());
        return response()->json(new NetworkAlertResource($alert->fresh()));
    }

    public function resolve(NetworkAlert $alert): JsonResponse
    {
        $this->authorize('update', $alert);
        $alert->resolve();
        return response()->json(new NetworkAlertResource($alert->fresh()));
    }
}
