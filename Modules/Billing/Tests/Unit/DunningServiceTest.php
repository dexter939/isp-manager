<?php

declare(strict_types=1);

namespace Modules\Billing\Tests\Unit;

use Carbon\Carbon;
use Modules\Billing\Enums\DunningAction;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Models\DunningStep;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Services\DunningService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class DunningServiceTest extends TestCase
{
    use RefreshDatabase;

    private DunningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DunningService::class);
    }

    /** @test */
    public function it_skips_steps_when_invoice_is_already_paid(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid->value]);
        $step    = DunningStep::factory()->create([
            'invoice_id'   => $invoice->id,
            'action'       => DunningAction::EmailReminder->value,
            'status'       => 'pending',
            'scheduled_at' => now()->subHour(),
        ]);

        $this->service->executeStep($step);

        $this->assertEquals('skipped', $step->fresh()->status);
    }

    /** @test */
    public function it_processes_email_reminder_step(): void
    {
        Log::shouldReceive('info')->once()->withArgs(fn($msg) => str_contains($msg, 'email reminder'));

        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Overdue->value]);
        $step    = DunningStep::factory()->create([
            'invoice_id'   => $invoice->id,
            'action'       => DunningAction::EmailReminder->value,
            'status'       => 'pending',
            'scheduled_at' => now()->subHour(),
        ]);

        $this->service->executeStep($step);

        $this->assertEquals('executed', $step->fresh()->status);
        $this->assertNotNull($step->fresh()->executed_at);
    }

    /** @test */
    public function it_processes_pending_due_steps_in_batch(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Overdue->value]);

        // 3 step scaduti
        DunningStep::factory()->count(3)->create([
            'invoice_id'   => $invoice->id,
            'action'       => DunningAction::EmailReminder->value,
            'status'       => 'pending',
            'scheduled_at' => now()->subDay(),
        ]);
        // 1 step futuro (non deve essere eseguito)
        DunningStep::factory()->create([
            'invoice_id'   => $invoice->id,
            'action'       => DunningAction::SmsReminder->value,
            'status'       => 'pending',
            'scheduled_at' => now()->addDay(),
        ]);

        ['executed' => $executed] = $this->service->processScheduledSteps();

        $this->assertEquals(3, $executed);
    }

    /** @test */
    public function it_does_not_execute_future_steps(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Overdue->value]);
        DunningStep::factory()->create([
            'invoice_id'   => $invoice->id,
            'action'       => DunningAction::Suspension->value,
            'status'       => 'pending',
            'scheduled_at' => now()->addDays(5),
        ]);

        ['executed' => $executed] = $this->service->processScheduledSteps();

        $this->assertEquals(0, $executed);
    }
}
