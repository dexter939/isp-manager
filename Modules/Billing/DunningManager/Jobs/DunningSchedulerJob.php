<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Billing\DunningManager\Models\DunningCase;
use Modules\Billing\DunningManager\Services\DunningManager;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Models\Invoice;

class DunningSchedulerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600; // 1 hour lock for ShouldBeUnique

    public function __construct()
    {
        $this->onQueue('billing');
    }

    /**
     * Execute the dunning scheduler job.
     *
     * Step 1: Find all overdue invoices (due_date < today, status=pending, no open dunning case)
     *         and call DunningManager::startForInvoice().
     *
     * Step 2: Find all open dunning cases where next_action_at <= now()
     *         and call DunningManager::processStep().
     */
    public function handle(DunningManager $manager): void
    {
        $this->openNewCases($manager);
        $this->processDueCases($manager);
    }

    private function openNewCases(DunningManager $manager): void
    {
        // Find overdue invoices with no open dunning case
        $overdueInvoices = Invoice::query()
            ->where('due_date', '<', today())
            ->where('status', InvoiceStatus::Pending->value)
            ->whereDoesntHave('dunningCases', function ($q) {
                $q->where('status', 'open');
            })
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                $manager->startForInvoice($invoice);
            } catch (\Throwable $e) {
                Log::error('DunningSchedulerJob: failed to open case', [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info('DunningSchedulerJob: opened new cases', [
            'count' => $overdueInvoices->count(),
        ]);
    }

    private function processDueCases(DunningManager $manager): void
    {
        $dueCases = DunningCase::query()
            ->open()
            ->due()
            ->with(['policy', 'contract', 'invoice', 'customer'])
            ->get();

        foreach ($dueCases as $case) {
            try {
                $manager->processStep($case);
            } catch (\Throwable $e) {
                Log::error('DunningSchedulerJob: failed to process step', [
                    'case_id' => $case->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        Log::info('DunningSchedulerJob: processed due cases', [
            'count' => $dueCases->count(),
        ]);
    }
}
