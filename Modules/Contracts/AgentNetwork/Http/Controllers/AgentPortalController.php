<?php

namespace Modules\Contracts\AgentNetwork\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contracts\AgentNetwork\Models\Agent;
use Modules\Contracts\AgentNetwork\Models\CommissionEntry;
use Modules\Contracts\AgentNetwork\Models\CommissionLiquidation;
use Modules\Contracts\AgentNetwork\Http\Resources\AgentResource;
use Modules\Contracts\AgentNetwork\Http\Resources\CommissionEntryResource;

class AgentPortalController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $agent = Agent::where('user_id', $request->user()->id)->firstOrFail();
        return response()->json(new AgentResource($agent));
    }

    public function myCommissions(Request $request): JsonResponse
    {
        $agent   = Agent::where('user_id', $request->user()->id)->firstOrFail();
        $entries = CommissionEntry::where('agent_id', $agent->id)->latest()->paginate(20);
        return response()->json(CommissionEntryResource::collection($entries));
    }

    public function myLiquidations(Request $request): JsonResponse
    {
        $agent        = Agent::where('user_id', $request->user()->id)->firstOrFail();
        $liquidations = CommissionLiquidation::where('agent_id', $agent->id)->latest()->paginate(20);
        return response()->json(['data' => $liquidations]);
    }

    public function myContracts(Request $request): JsonResponse
    {
        $agent     = Agent::where('user_id', $request->user()->id)->firstOrFail();
        $contracts = $agent->commissionEntries()->with('contract')->distinct('contract_id')->paginate(20);
        return response()->json(['data' => $contracts]);
    }
}
