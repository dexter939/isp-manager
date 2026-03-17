<?php

namespace Modules\Billing\DunningManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\DunningManager\Models\DunningCase;
use Modules\Billing\DunningManager\Models\DunningPolicy;
use Modules\Billing\DunningManager\Models\DunningWhitelist;
use Modules\Billing\DunningManager\Services\DunningManager;
use Modules\Billing\DunningManager\Http\Requests\StorePolicyRequest;
use Modules\Billing\DunningManager\Http\Requests\UpdatePolicyRequest;
use Modules\Billing\DunningManager\Http\Requests\AddToWhitelistRequest;

class DunningController extends ApiController
{
    public function __construct(private readonly DunningManager $manager) {}

    public function index(Request $request): JsonResponse
    {
        $cases = DunningCase::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->with(['policy'])
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $cases]);
    }

    public function show(int $id): JsonResponse
    {
        $case = DunningCase::with(['steps', 'policy'])->findOrFail($id);
        return response()->json(['data' => $case]);
    }

    public function resolve(int $id): JsonResponse
    {
        $this->authorize('billing');
        $case = DunningCase::findOrFail($id);
        $this->manager->resolveOnPayment($case->contract_id);
        return response()->json(['message' => 'Caso risolto.']);
    }

    public function policies(): JsonResponse
    {
        return response()->json(['data' => DunningPolicy::all()]);
    }

    public function storePolicy(StorePolicyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $policy = DunningPolicy::create($validated);
        return response()->json(['data' => $policy, 'message' => 'Policy creata.'], 201);
    }

    public function updatePolicy(UpdatePolicyRequest $request, int $id): JsonResponse
    {
        $policy    = DunningPolicy::findOrFail($id);
        $validated = $request->validated();
        $policy->update($validated);
        return response()->json(['data' => $policy, 'message' => 'Policy aggiornata.']);
    }

    public function whitelist(): JsonResponse
    {
        return response()->json(['data' => DunningWhitelist::with('customer')->paginate(20)]);
    }

    public function addToWhitelist(AddToWhitelistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $entry = DunningWhitelist::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $entry, 'message' => 'Cliente aggiunto alla whitelist.'], 201);
    }
}
