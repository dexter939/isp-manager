<?php
namespace Modules\Billing\Cdr\Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Cdr\Models\CdrRate;
use Modules\Billing\Cdr\Models\CdrTariffPlan;
use Modules\Billing\Cdr\Services\CdrImporter;
use Tests\TestCase;

class CdrImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_asterisk_format_csv(): void
    {
        $plan = CdrTariffPlan::factory()->create(['is_default' => true]);
        CdrRate::factory()->create(['tariff_plan_id' => $plan->id, 'prefix' => '0039', 'rate_per_minute_cents' => 10]);
        config(['cdr.default_tariff_plan' => $plan->id]);

        $csv = "accountcode,src,dst,dcontext,clid,channel,dstchannel,lastapp,lastdata,start,answer,end,duration,billsec\n";
        $csv .= "ISP,+393331234567,00390612345678,default,Mario,SIP/001,SIP/002,Dial,SIP/002,2024-01-01 10:00:00,2024-01-01 10:00:05,2024-01-01 10:01:35,95,90\n";

        $importer = app(CdrImporter::class);
        $file     = $importer->import($csv, 'test.csv', 'asterisk');

        $this->assertEquals('completed', $file->status);
        $this->assertEquals(1, $file->records_imported);
        $this->assertDatabaseHas('cdr_records', ['caller_number' => '+393331234567']);
    }

    public function test_longest_prefix_match(): void
    {
        $plan = CdrTariffPlan::factory()->create();
        CdrRate::factory()->create(['tariff_plan_id' => $plan->id, 'prefix' => '0039', 'destination_name' => 'Italia Generico', 'rate_per_minute_cents' => 10]);
        CdrRate::factory()->create(['tariff_plan_id' => $plan->id, 'prefix' => '003906', 'destination_name' => 'Roma', 'rate_per_minute_cents' => 5]);

        $importer = app(CdrImporter::class);
        $rate     = $importer->resolveRate('0039061234567', $plan->id);

        $this->assertEquals('003906', $rate->prefix);
        $this->assertEquals('Roma', $rate->destination_name);
    }

    public function test_calculates_cost_correctly(): void
    {
        $rate   = new CdrRate(['rate_per_minute_cents' => 10, 'connection_fee_cents' => 0, 'billing_interval_seconds' => 60]);
        $record = new \Modules\Billing\Cdr\Models\CdrRecord(['duration_seconds' => 90]);

        $importer = app(CdrImporter::class);
        $cost     = $importer->calculateCost($record, $rate);

        // 90s = 2 intervals of 60s = 2 * €0.10 = €0.20
        $this->assertEquals(20, $cost->getMinorAmount()->toInt());
    }
}
