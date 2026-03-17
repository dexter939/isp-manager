<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Network\Http\Requests\FloatingIpRequest;
use Modules\Network\Http\Requests\UpdateFloatingIpRequest;
use Modules\Network\Models\FloatingIpPair;
use Modules\Network\Services\FloatingIpService;

class FloatingIpController extends ApiController
{
    public function __construct(
        private readonly FloatingIpService $floatingIpService,
    ) {}

    /**
     * Paginated list of floating IP pairs for the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $pairs = FloatingIpPair::where('tenant_id', $request->user()->tenant_id)
            ->with(['resources'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success($pairs);
    }

    /**
     * Create a new floating IP pair with resources.
     */
    public function store(FloatingIpRequest $request): JsonResponse
    {
        $data = array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id]
        );

        $pair = $this->floatingIpService->createPair($data);

        return $this->created(['data' => $pair]);
    }

    /**
     * Show a single pair with resources and the last 20 events.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $pair = FloatingIpPair::where('tenant_id', $request->user()->tenant_id)
            ->with([
                'resources',
                'events' => fn ($q) => $q->orderByDesc('created_at')->limit(20),
            ])
            ->findOrFail($id);

        return $this->success(['data' => $pair]);
    }

    /**
     * Update the pair name.
     */
    public function update(UpdateFloatingIpRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $pair = FloatingIpPair::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $pair->update(['name' => $validated['name']]);

        return $this->success(['data' => $pair->fresh('resources')]);
    }

    /**
     * Delete a pair: remove RADIUS attributes and soft-delete the record.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $pair = FloatingIpPair::where('tenant_id', $request->user()->tenant_id)
            ->with('resources')
            ->findOrFail($id);

        DB::transaction(function () use ($pair): void {
            // Remove radreply entries for both master and failover accounts
            foreach ($pair->resources as $resource) {
                $attribute = match ($resource->resource_type->value) {
                    'ipv4'        => 'Framed-IP-Address',
                    'ipv4_subnet' => 'Framed-Route',
                    'ipv6_prefix' => 'Framed-IPv6-Prefix',
                    default       => 'Framed-IP-Address',
                };

                DB::table('radreply')
                    ->where('username', $pair->master_pppoe_account_id)
                    ->where('attribute', $attribute)
                    ->where('value', $resource->resource_value)
                    ->delete();

                DB::table('radreply')
                    ->where('username', $pair->failover_pppoe_account_id)
                    ->where('attribute', $attribute)
                    ->where('value', $resource->resource_value)
                    ->delete();
            }

            $pair->delete();

            Log::info('[FloatingIp] Pair deleted', [
                'pair_id'   => $pair->id,
                'tenant_id' => $pair->tenant_id,
            ]);
        });

        return $this->noContent();
    }

    /**
     * Manually force a failover for the given pair.
     */
    public function forceFailover(Request $request, string $id): JsonResponse
    {
        $pair = FloatingIpPair::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $this->floatingIpService->triggerFailover($pair, 'manual');

        return $this->success(['data' => $pair->fresh(['resources', 'events'])]);
    }

    /**
     * Manually force a recovery for the given pair.
     */
    public function forceRecovery(Request $request, string $id): JsonResponse
    {
        $pair = FloatingIpPair::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $this->floatingIpService->triggerRecovery($pair);

        return $this->success(['data' => $pair->fresh(['resources', 'events'])]);
    }
}
