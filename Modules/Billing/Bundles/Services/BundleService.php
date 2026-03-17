<?php
namespace Modules\Billing\Bundles\Services;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Bundles\Models\BundlePlan;
use Modules\Billing\Bundles\Models\BundleSubscription;
class BundleService {
    public function calculateDiscount(BundlePlan $plan): Money {
        $listTotal  = $plan->items->sum('list_price_amount');
        $discountCents = $listTotal - $plan->price_amount;
        return Money::ofMinor(max(0, $discountCents), $plan->price_currency);
    }
    public function generateInvoiceLines(BundleSubscription $sub): Collection {
        $plan  = $sub->plan->loadMissing('items');
        $lines = $plan->items->map(fn($item) => ['description'=>$item->description,'service_type'=>$item->service_type,'amount_cents'=>$item->list_price_amount,'sort_order'=>$item->sort_order]);
        $discount = $this->calculateDiscount($plan);
        if ($discount->getMinorAmount()->toInt() > 0) {
            $lines->push(['description'=>"Sconto Bundle {$plan->name}",'service_type'=>'discount','amount_cents'=>-$discount->getMinorAmount()->toInt(),'sort_order'=>99]);
        }
        return $lines;
    }
    public function activateBundle(string $contractId, BundlePlan $plan, ?int $customPriceCents = null): BundleSubscription {
        return DB::transaction(function () use ($contractId, $plan, $customPriceCents) {
            $sub = BundleSubscription::create(['contract_id'=>$contractId,'bundle_plan_id'=>$plan->id,'custom_price_amount'=>$customPriceCents,'start_date'=>today(),'status'=>'active']);
            // Activate each service in the bundle
            foreach ($plan->items as $item) {
                if ($item->service_id) {
                    // Link service to contract (implementation depends on existing services structure)
                    DB::table('contract_services')->updateOrInsert(['contract_id'=>$contractId,'service_id'=>$item->service_id],['bundle_subscription_id'=>$sub->id,'activated_at'=>now(),'status'=>'active']);
                }
            }
            return $sub;
        });
    }
    public function terminateBundle(BundleSubscription $sub): void {
        DB::transaction(function () use ($sub) {
            $sub->update(['status'=>'terminated','end_date'=>today()]);
            // Deactivate services linked to this bundle
            DB::table('contract_services')->where('bundle_subscription_id', $sub->id)->update(['status'=>'terminated','terminated_at'=>now()]);
        });
    }
}
