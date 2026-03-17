<?php
namespace Modules\Billing\PaymentMatching\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\PaymentMatching\Models\PaymentMatchingRule;
use Modules\Billing\PaymentMatching\Services\PaymentMatchingEngine;
use Modules\Billing\PaymentMatching\Http\Requests\StoreMatchingRuleRequest;
use Modules\Billing\PaymentMatching\Http\Requests\UpdateMatchingRuleRequest;
use Modules\Billing\PaymentMatching\Http\Requests\ReorderRulesRequest;
use Modules\Billing\PaymentMatching\Http\Requests\SimulateMatchingRequest;
class PaymentMatchingController extends ApiController {
    public function __construct(private PaymentMatchingEngine $engine) {}
    public function index(): JsonResponse {
        return response()->json(PaymentMatchingRule::orderBy('priority')->paginate(50));
    }
    public function store(StoreMatchingRuleRequest $request): JsonResponse {
        $data = $request->validated();
        $rule = PaymentMatchingRule::create($data);
        return response()->json($rule, 201);
    }
    public function update(UpdateMatchingRuleRequest $request, PaymentMatchingRule $rule): JsonResponse {
        $data = $request->validated();
        $rule->update($data);
        return response()->json($rule);
    }
    public function destroy(PaymentMatchingRule $rule): JsonResponse {
        if ($rule->is_system) return response()->json(['error' => 'Cannot delete system rules'], 422);
        $rule->delete();
        return response()->json(null, 204);
    }
    public function reorder(ReorderRulesRequest $request): JsonResponse {
        foreach ($request->validated()['rules'] as $item) {
            PaymentMatchingRule::where('id', $item['id'])->update(['priority' => $item['priority']]);
        }
        return response()->json(['message' => 'Reordered']);
    }
    public function simulate(SimulateMatchingRequest $request): JsonResponse {
        $result = $this->engine->simulate($request->validated()['payment_data']);
        return response()->json($result);
    }
    public function toggle(PaymentMatchingRule $rule): JsonResponse {
        $rule->update(['is_active' => !$rule->is_active]);
        return response()->json($rule);
    }
}
