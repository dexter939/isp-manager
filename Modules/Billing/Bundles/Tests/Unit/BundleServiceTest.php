<?php
namespace Modules\Billing\Bundles\Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Bundles\Models\BundlePlan;
use Modules\Billing\Bundles\Models\BundlePlanItem;
use Modules\Billing\Bundles\Services\BundleService;
class BundleServiceTest extends TestCase {
    use RefreshDatabase;
    private BundleService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new BundleService(); }
    public function test_calculate_discount_bundle_cheaper_than_sum(): void {
        $plan = BundlePlan::factory()->create(['price_amount'=>2900,'price_currency'=>'EUR']);
        BundlePlanItem::factory()->create(['bundle_plan_id'=>$plan->id,'list_price_amount'=>2000]);
        BundlePlanItem::factory()->create(['bundle_plan_id'=>$plan->id,'list_price_amount'=>1500]);
        $plan->load('items');
        $discount = $this->service->calculateDiscount($plan);
        $this->assertEquals(600, $discount->getMinorAmount()->toInt(), "Discount = 3500 - 2900 = 600 centesimi");
    }
    public function test_generate_invoice_lines_includes_discount_row(): void {
        $plan = BundlePlan::factory()->create(['price_amount'=>2900]);
        BundlePlanItem::factory()->create(['bundle_plan_id'=>$plan->id,'list_price_amount'=>2000,'description'=>'Internet Fibra']);
        BundlePlanItem::factory()->create(['bundle_plan_id'=>$plan->id,'list_price_amount'=>1500,'description'=>'VoIP']);
        $plan->load('items');
        $sub   = \Modules\Billing\Bundles\Models\BundleSubscription::factory()->create(['bundle_plan_id'=>$plan->id]);
        $sub->setRelation('plan', $plan);
        $lines = $this->service->generateInvoiceLines($sub);
        $discountLine = $lines->firstWhere('service_type','discount');
        $this->assertNotNull($discountLine);
        $this->assertEquals(-600, $discountLine['amount_cents']);
    }
}
