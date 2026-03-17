<?php

namespace Modules\Maintenance\RouteOptimizer\Http\Controllers;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Maintenance\RouteOptimizer\Http\Requests\OptimizeRouteRequest;
use Modules\Maintenance\RouteOptimizer\Http\Requests\ReorderRoutePlanRequest;
use Modules\Maintenance\RouteOptimizer\Models\RoutePlan;
use Modules\Maintenance\RouteOptimizer\Services\RouteOptimizerService;

class RouteOptimizerController extends ApiController
{
    public function __construct(private RouteOptimizerService $service) {}

    public function optimize(OptimizeRouteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $plan = $this->service->optimize($data['technician_id'], Carbon::parse($data['date']));
        return response()->json($plan, 201);
    }

    public function plan(string $date, string $userId): JsonResponse
    {
        $plan = RoutePlan::where('technician_id', $userId)->where('plan_date', $date)->firstOrFail();
        return response()->json($plan);
    }

    public function reorder(ReorderRoutePlanRequest $request, RoutePlan $plan): JsonResponse
    {
        $data = $request->validated();
        $plan->update(['optimized_order' => $data['optimized_order']]);
        return response()->json($plan);
    }

    public function directions(RoutePlan $plan): JsonResponse
    {
        return response()->json($this->service->getDirections($plan));
    }
}
