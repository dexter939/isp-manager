<?php
namespace Modules\Network\FairUsage\Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Network\FairUsage\Services\TrafficAccountingService;
use Modules\Network\FairUsage\Services\FupEnforcementService;
class FupEnforcementTest extends TestCase {
    use RefreshDatabase;
    public function test_fup_triggered_when_usage_reaches_cap(): void {
        config(['app.carrier_mock'=>true]);
        $serviceId = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('services')->insert(['id'=>$serviceId,'name'=>'Fibra 100M','cap_enabled'=>true,'cap_gb'=>10,'fup_threshold_percent'=>100,'fup_service_id'=>\Illuminate\Support\Str::uuid()]);
        $accountId = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('pppoe_accounts')->insert(['id'=>$accountId,'username'=>'testuser','service_id'=>$serviceId]);
        $fupService = new FupEnforcementService();
        $service    = new TrafficAccountingService($fupService);
        // Simulate exactly 10GB of traffic
        $bytes = 10 * 1073741824;
        $service->updateUsage($accountId, intdiv($bytes, 2), intdiv($bytes, 2));
        $usage = \Illuminate\Support\Facades\DB::table('customer_traffic_usage')->where('pppoe_account_id', $accountId)->first();
        $this->assertTrue((bool)$usage->fup_triggered);
    }
    public function test_topup_removes_fup(): void {
        config(['app.carrier_mock'=>true]);
        $accountId = \Illuminate\Support\Str::uuid();
        $year = (int)date('Y');
        $month = (int)date('n');
        \Illuminate\Support\Facades\DB::table('customer_traffic_usage')->insert(['pppoe_account_id'=>$accountId,'period_year'=>$year,'period_month'=>$month,'bytes_total'=>10*1073741824,'cap_gb'=>10,'fup_triggered'=>true,'topup_gb_added'=>0,'last_updated'=>now()]);
        $product = \Modules\Network\FairUsage\Models\FupTopupProduct::create(['name'=>'Top-Up 10GB','gb_amount'=>10,'price_amount'=>500,'is_active'=>true]);
        \Illuminate\Support\Facades\DB::table('pppoe_accounts')->insert(['id'=>$accountId,'username'=>'testuser2','service_id'=>\Illuminate\Support\Str::uuid()]);
        $account = \Illuminate\Support\Facades\DB::table('pppoe_accounts')->find($accountId);
        $fupService = new FupEnforcementService();
        $topupService = new \Modules\Network\FairUsage\Services\TopupService($fupService);
        $topupService->purchase($account, $product, 'prepaid');
        $usage = \Illuminate\Support\Facades\DB::table('customer_traffic_usage')->where('pppoe_account_id', $accountId)->first();
        $this->assertFalse((bool)$usage->fup_triggered);
    }
}
