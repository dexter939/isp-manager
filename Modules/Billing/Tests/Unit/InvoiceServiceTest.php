<?php

declare(strict_types=1);

namespace Modules\Billing\Tests\Unit;

use Carbon\Carbon;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\InvoiceItem;
use Modules\Billing\Models\DunningStep;
use Modules\Billing\Services\InvoiceService;
use Modules\Contracts\Enums\BillingCycle;
use Modules\Contracts\Enums\ContractStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvoiceService::class);
    }

    /** @test */
    public function it_generates_invoice_for_active_contract(): void
    {
        $contract = $this->makeActiveContract();

        $invoice = $this->service->generateForContract($contract);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(InvoiceStatus::Draft, $invoice->status);
        $this->assertEquals($contract->id, $invoice->contract_id);
        $this->assertGreaterThan(0, (float) $invoice->total);
    }

    /** @test */
    public function it_adds_canone_item_to_invoice(): void
    {
        $contract = $this->makeActiveContract(monthlyPrice: '29.90');

        $invoice = $this->service->generateForContract($contract);

        $this->assertCount(1, $invoice->items);
        $item = $invoice->items->first();
        $this->assertEquals('canone', $item->type);
        $this->assertEquals('29.90', $item->total_net);
    }

    /** @test */
    public function it_adds_activation_fee_on_first_invoice(): void
    {
        $contract = $this->makeActiveContract(activationFee: '49.00');

        $invoice = $this->service->generateForContract($contract);

        $types = $invoice->items->pluck('type')->all();
        $this->assertContains('attivazione', $types);
    }

    /** @test */
    public function it_does_not_add_activation_fee_on_second_invoice(): void
    {
        $contract = $this->makeActiveContract(activationFee: '49.00');

        // Prima fattura
        Invoice::factory()->create([
            'contract_id' => $contract->id,
            'status'      => InvoiceStatus::Paid->value,
        ]);

        $invoice = $this->service->generateForContract($contract);

        $types = $invoice->items->pluck('type')->all();
        $this->assertNotContains('attivazione', $types);
    }

    /** @test */
    public function it_calculates_totals_correctly_with_22_percent_vat(): void
    {
        $contract = $this->makeActiveContract(monthlyPrice: '100.00');

        $invoice = $this->service->generateForContract($contract);

        $this->assertEquals('100.00', $invoice->subtotal);
        $this->assertEquals('22.00', $invoice->tax_amount);
        $this->assertEquals('122.00', $invoice->total);
    }

    /** @test */
    public function it_issues_invoice_from_draft(): void
    {
        $contract = $this->makeActiveContract();
        $invoice  = $this->service->generateForContract($contract);

        $issued = $this->service->issue($invoice);

        $this->assertEquals(InvoiceStatus::Issued, $issued->status);
    }

    /** @test */
    public function it_schedules_six_dunning_steps_on_issue(): void
    {
        $contract = $this->makeActiveContract();
        $invoice  = $this->service->generateForContract($contract);
        $this->service->issue($invoice);

        $this->assertCount(6, $invoice->fresh()->dunningSteps);
    }

    /** @test */
    public function it_schedules_dunning_steps_at_correct_offsets(): void
    {
        $contract = $this->makeActiveContract();
        $invoice  = $this->service->generateForContract($contract);
        $this->service->issue($invoice);

        $steps    = $invoice->fresh()->dunningSteps()->orderBy('step')->get();
        $dueDate  = Carbon::parse($invoice->due_date);

        $this->assertTrue($steps[0]->scheduled_at->isSameDay($dueDate->copy()->addDays(10)));  // email
        $this->assertTrue($steps[1]->scheduled_at->isSameDay($dueDate->copy()->addDays(15)));  // sms
        $this->assertTrue($steps[2]->scheduled_at->isSameDay($dueDate->copy()->addDays(20)));  // whatsapp
        $this->assertTrue($steps[3]->scheduled_at->isSameDay($dueDate->copy()->addDays(25)));  // suspension
        $this->assertTrue($steps[4]->scheduled_at->isSameDay($dueDate->copy()->addDays(30)));  // retry SDD
        $this->assertTrue($steps[5]->scheduled_at->isSameDay($dueDate->copy()->addDays(45)));  // termination
    }

    /** @test */
    public function it_marks_invoice_as_paid(): void
    {
        $contract = $this->makeActiveContract();
        $invoice  = $this->service->generateForContract($contract);
        $this->service->issue($invoice);

        $paid = $this->service->markPaid($invoice->fresh(), 'bonifico');

        $this->assertEquals(InvoiceStatus::Paid, $paid->status);
        $this->assertNotNull($paid->paid_at);
    }

    /** @test */
    public function it_skips_dunning_steps_when_paid(): void
    {
        $contract = $this->makeActiveContract();
        $invoice  = $this->service->generateForContract($contract);
        $this->service->issue($invoice);
        $this->service->markPaid($invoice->fresh(), 'bonifico');

        $pending = DunningStep::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->count();

        $this->assertEquals(0, $pending);
    }

    /** @test */
    public function it_throws_when_invalid_status_transition(): void
    {
        $contract = $this->makeActiveContract();
        $invoice  = $this->service->generateForContract($contract);

        $this->expectException(\DomainException::class);

        // Tentare di pagare una fattura Draft (non Issued)
        $this->service->markPaid($invoice, 'contanti');
    }

    /** @test */
    public function it_generates_sequential_invoice_numbers(): void
    {
        $contract = $this->makeActiveContract();

        $inv1 = $this->service->generateForContract($contract);
        $this->service->issue($inv1);

        $inv2 = $this->service->generateForContract($contract);
        $this->service->issue($inv2);

        $year = now()->year;
        $this->assertEquals("{$year}/000001", $inv1->number);
        $this->assertEquals("{$year}/000002", $inv2->number);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeActiveContract(
        string $monthlyPrice = '25.00',
        string $activationFee = '0.00',
    ) {
        $tenant   = \App\Models\Tenant::factory()->create();
        $customer = \Modules\Contracts\Models\Customer::factory()->create(['tenant_id' => $tenant->id]);
        $plan     = \Modules\Contracts\Models\ServicePlan::factory()->create([
            'monthly_price' => $monthlyPrice,
        ]);

        return \Modules\Contracts\Models\Contract::factory()->create([
            'tenant_id'      => $tenant->id,
            'customer_id'    => $customer->id,
            'service_plan_id' => $plan->id,
            'status'         => ContractStatus::Active->value,
            'billing_cycle'  => BillingCycle::Monthly->value,
            'billing_day'    => now()->day,
            'monthly_price'  => $monthlyPrice,
            'activation_fee' => $activationFee,
            'modem_fee'      => '0.00',
        ]);
    }
}
