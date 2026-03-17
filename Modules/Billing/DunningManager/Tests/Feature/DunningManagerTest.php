<?php

namespace Modules\Billing\DunningManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\Invoice;
use Modules\Billing\DunningManager\Models\DunningCase;
use Modules\Billing\DunningManager\Models\DunningPolicy;
use Modules\Billing\DunningManager\Models\DunningWhitelist;
use Modules\Billing\DunningManager\Services\DunningManager;
use Tests\TestCase;

class DunningManagerTest extends TestCase
{
    use RefreshDatabase;

    private DunningManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(DunningManager::class);
    }

    public function test_starts_dunning_case_for_overdue_invoice(): void
    {
        $invoice = Invoice::factory()->overdue()->create();

        $case = $this->manager->startForInvoice($invoice);

        $this->assertNotNull($case);
        $this->assertInstanceOf(DunningCase::class, $case);
        $this->assertEquals('open', $case->status);
        $this->assertDatabaseHas('dunning_cases', ['invoice_id' => $invoice->id]);
    }

    public function test_skips_whitelisted_customer(): void
    {
        $invoice = Invoice::factory()->overdue()->create();

        DunningWhitelist::create([
            'customer_id' => $invoice->customer_id,
            'reason'      => 'VIP client',
            'created_by'  => 1,
        ]);

        $case = $this->manager->startForInvoice($invoice);
        $this->assertNull($case);
    }

    public function test_resolves_case_on_payment(): void
    {
        $case = DunningCase::factory()->open()->create();

        $this->manager->resolveOnPayment($case->contract_id);

        $case->refresh();
        $this->assertEquals('resolved', $case->status);
        $this->assertNotNull($case->resolved_at);
    }

    public function test_executes_suspend_step(): void
    {
        config(['app.carrier_mock' => true]);
        $case = DunningCase::factory()->open()->withSuspendStep()->create();

        $this->manager->processStep($case);

        // In mock mode, contract should be suspended
        $this->assertDatabaseHas('dunning_steps', [
            'case_id' => $case->id,
            'action'  => 'suspend',
        ]);
    }
}
