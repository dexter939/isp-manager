<?php
namespace Modules\Billing\Cdr\Tests\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Cdr\Models\CdrRecord;
use Modules\Billing\Cdr\Services\CdrBillingService;
use Tests\TestCase;

class CdrBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_records_by_category(): void
    {
        CdrRecord::factory()->count(3)->create(['category' => 'national', 'billed' => false, 'customer_id' => 1, 'start_time' => now()]);
        CdrRecord::factory()->count(2)->create(['category' => 'mobile', 'billed' => false, 'customer_id' => 1, 'start_time' => now()]);

        $service = app(CdrBillingService::class);
        $lines   = $service->generateInvoiceLines(1, 99, now()->startOfMonth(), now()->endOfMonth());

        $categories = array_column($lines, 'category');
        $this->assertContains('national', $categories);
        $this->assertContains('mobile', $categories);
        $this->assertCount(2, $lines);
    }

    public function test_marks_records_as_billed_after_invoicing(): void
    {
        CdrRecord::factory()->count(2)->create(['billed' => false, 'customer_id' => 1, 'start_time' => now()]);

        $service = app(CdrBillingService::class);
        $service->generateInvoiceLines(1, 99, now()->startOfMonth(), now()->endOfMonth());

        $this->assertEquals(0, CdrRecord::where('customer_id', 1)->where('billed', false)->count());
    }
}
