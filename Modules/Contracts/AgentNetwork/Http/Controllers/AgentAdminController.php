<?php

namespace Modules\Contracts\AgentNetwork\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Contracts\AgentNetwork\Http\Requests\StoreAgentRequest;
use Modules\Contracts\AgentNetwork\Http\Requests\UpdateAgentRequest;
use Modules\Contracts\AgentNetwork\Models\Agent;
use Modules\Contracts\AgentNetwork\Models\CommissionLiquidation;
use Modules\Contracts\AgentNetwork\Services\CommissionLiquidationService;
use Modules\Contracts\AgentNetwork\Http\Resources\AgentResource;

class AgentAdminController extends ApiController
{
    public function __construct(private readonly CommissionLiquidationService $liquidationService) {}

    public function index(): JsonResponse
    {
        return response()->json(AgentResource::collection(Agent::with('user')->paginate(20)));
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $prefix = config('agent_network.code_prefix', 'AGT');
        $code   = $prefix . '-' . str_pad((string) (Agent::count() + 1), 4, '0', STR_PAD_LEFT);

        $agent = Agent::create([...$validated, 'code' => $code]);

        return response()->json(['data' => new AgentResource($agent), 'message' => 'Agente creato.'], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new AgentResource(Agent::with(['user', 'commissionEntries', 'liquidations'])->findOrFail($id)));
    }

    public function update(UpdateAgentRequest $request, int $id): JsonResponse
    {
        $agent     = Agent::findOrFail($id);
        $validated = $request->validated();
        $agent->update($validated);
        return response()->json(['data' => new AgentResource($agent), 'message' => 'Agente aggiornato.']);
    }

    public function liquidations(): JsonResponse
    {
        return response()->json(['data' => CommissionLiquidation::with('agent')->latest()->paginate(20)]);
    }

    public function approveLiquidation(Request $request, int $id): JsonResponse
    {
        $liquidation = CommissionLiquidation::findOrFail($id);
        $this->liquidationService->approveLiquidation($liquidation, $request->user());
        return response()->json(['message' => 'Liquidazione approvata.']);
    }

    public function markLiquidationPaid(int $id): JsonResponse
    {
        $liquidation = CommissionLiquidation::findOrFail($id);
        $this->liquidationService->markAsPaid($liquidation);
        return response()->json(['message' => 'Liquidazione marcata come pagata.']);
    }
}
