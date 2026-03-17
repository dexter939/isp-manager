<?php
namespace Modules\Billing\Bundles\Http\Controllers;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use Modules\Billing\Bundles\Models\BundlePlan;
use Modules\Billing\Bundles\Models\BundlePlanItem;
use Modules\Billing\Bundles\Models\BundleSubscription;
use Modules\Billing\Bundles\Services\BundleService;
use Modules\Billing\Bundles\Http\Requests\StoreBundleRequest;
use Modules\Billing\Bundles\Http\Requests\UpdateBundleRequest;
use Modules\Billing\Bundles\Http\Requests\SubscribeBundleRequest;
class BundleController extends ApiController {
    public function __construct(private BundleService $service) {}
    public function index(): JsonResponse { return response()->json(BundlePlan::with('items')->where('is_active',true)->paginate(20)); }
    public function store(StoreBundleRequest $request): JsonResponse {
        $data = $request->validated();
        $plan  = BundlePlan::create(\Arr::except($data,'items'));
        foreach ($data['items'] as $item) {
            BundlePlanItem::create(array_merge($item, ['bundle_plan_id'=>$plan->id]));
        }
        return response()->json($plan->load('items'), 201);
    }
    public function show(BundlePlan $plan): JsonResponse { return response()->json($plan->load('items')); }
    public function update(UpdateBundleRequest $request, BundlePlan $plan): JsonResponse {
        $plan->update($request->validated());
        return response()->json($plan);
    }
    public function destroy(BundlePlan $plan): JsonResponse { $plan->delete(); return response()->json(null,204); }
    public function subscribe(SubscribeBundleRequest $request): JsonResponse {
        $data = $request->validated();
        $plan = BundlePlan::findOrFail($data['bundle_plan_id']);
        $sub  = $this->service->activateBundle($data['contract_id'], $plan, $data['custom_price_amount'] ?? null);
        return response()->json($sub->load('plan'), 201);
    }
    public function subscriptionShow(BundleSubscription $subscription): JsonResponse { return response()->json($subscription->load('plan.items')); }
    public function terminateSubscription(BundleSubscription $subscription): JsonResponse {
        $this->service->terminateBundle($subscription);
        return response()->json(['message'=>'Bundle terminated']);
    }
    public function discount(BundlePlan $plan): JsonResponse {
        $plan->load('items');
        return response()->json(['plan_price_cents'=>$plan->price_amount,'list_total_cents'=>$plan->items->sum('list_price_amount'),'discount_cents'=>$this->service->calculateDiscount($plan)->getMinorAmount()->toInt()]);
    }
}
