<?php
namespace Modules\Network\FairUsage\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Network\FairUsage\Models\FupTopupProduct;
class FupFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_get_usage_endpoint(): void {
        $accountId = \Illuminate\Support\Str::uuid();
        $this->actingAsAdmin()->getJson("/api/fup/usage/{$accountId}")->assertOk()->assertJsonStructure(['pppoe_account_id','bytes_total','fup_triggered','usage_percent']);
    }
    public function test_get_topup_products(): void {
        FupTopupProduct::factory()->create(['name'=>'Top-Up 5GB','gb_amount'=>5,'price_amount'=>300,'is_active'=>true]);
        $this->actingAsAdmin()->getJson('/api/fup/topup-products')->assertOk()->assertJsonCount(1);
    }
    public function test_monthly_reset_clears_counters(): void {
        config(['app.carrier_mock'=>true]);
        $accountId = \Illuminate\Support\Str::uuid();
        $year = (int)date('Y');
        $month = (int)date('n');
        \Illuminate\Support\Facades\DB::table('customer_traffic_usage')->insert(['pppoe_account_id'=>$accountId,'period_year'=>$year,'period_month'=>$month,'bytes_total'=>5*1073741824,'cap_gb'=>10,'fup_triggered'=>false,'topup_gb_added'=>0,'last_updated'=>now()]);
        $serviceId = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('services')->insert(['id'=>$serviceId,'name'=>'Fibra','cap_enabled'=>true,'cap_gb'=>10]);
        \Illuminate\Support\Facades\DB::table('pppoe_accounts')->insert(['id'=>$accountId,'username'=>'u1','service_id'=>$serviceId]);
        $fupService = new \Modules\Network\FairUsage\Services\FupEnforcementService();
        $service    = new \Modules\Network\FairUsage\Services\TrafficAccountingService($fupService);
        $service->resetMonthlyCounters();
        $usage = \Illuminate\Support\Facades\DB::table('customer_traffic_usage')->where('pppoe_account_id',$accountId)->first();
        $this->assertEquals(0, $usage->bytes_total);
    }
}
