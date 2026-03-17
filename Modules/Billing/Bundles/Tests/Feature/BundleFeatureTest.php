<?php
namespace Modules\Billing\Bundles\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Bundles\Models\BundlePlan;
class BundleFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_create_bundle_plan_with_items(): void {
        $this->actingAsAdmin()->postJson('/api/bundles', ['name'=>'Fibra All-In','price_amount'=>2900,'billing_period'=>'monthly','items'=>[['service_type'=>'internet','description'=>'Internet 100M','list_price_amount'=>2000,'sort_order'=>1],['service_type'=>'voip','description'=>'VoIP 100 min','list_price_amount'=>1500,'sort_order'=>2]]])->assertStatus(201)->assertJsonPath('name','Fibra All-In')->assertJsonCount(2,'items');
    }
    public function test_discount_endpoint_returns_correct_values(): void {
        $plan = BundlePlan::factory()->create(['price_amount'=>2900]);
        \Modules\Billing\Bundles\Models\BundlePlanItem::factory()->create(['bundle_plan_id'=>$plan->id,'list_price_amount'=>2000]);
        \Modules\Billing\Bundles\Models\BundlePlanItem::factory()->create(['bundle_plan_id'=>$plan->id,'list_price_amount'=>1500]);
        $this->actingAsAdmin()->getJson("/api/bundles/{$plan->id}/discount")->assertOk()->assertJsonPath('discount_cents', 600);
    }
}
