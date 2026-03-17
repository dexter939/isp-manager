<?php

declare(strict_types=1);

namespace Modules\Billing\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Events\InvoiceGenerated;
use Modules\Billing\Events\InvoiceOverdue;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Services\InvoiceService;
use Modules\Billing\Services\SddService;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Contracts\Models\Contract;

/**
 * Job schedulato ogni giorno alle 00:30.
 *
 * Operazioni:
 * 1. Genera fatture per i contratti con billing_day = oggi
 * 2. Emette le fatture generate
 * 3. Genera il file SDD pain.008 per le fatture con metodo SDD in scadenza oggi
 * 4. Carica la carta Stripe per le fatture con metodo carta in scadenza oggi
 * 5. Segna come scadute le fatture non pagate oltre la due_date
 *
 * Schedulato in: AppServiceProvider → Schedule
 */
class BillingCycleJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function handle(InvoiceService $invoiceService, SddService $sddService): void
    {
        $today   = Carbon::today();
        $billingDay = $today->day;

        Log::info("BillingCycleJob: avvio per billing_day={$billingDay}, data={$today->toDateString()}");

        // ── 1. Genera fatture per contratti attivi con billing_day = oggi ────
        $contracts = Contract::where('status', ContractStatus::Active->value)
            ->where('billing_day', $billingDay)
            ->with(['customer', 'servicePlan', 'agent'])
            ->get();

        $generated = 0;
        foreach ($contracts as $contract) {
            try {
                $invoice = $invoiceService->generateForContract($contract);
                $invoiceService->issue($invoice);
                InvoiceGenerated::dispatch($invoice);
                $generated++;
            } catch (\Throwable $e) {
                Log::error("BillingCycle: errore generazione fattura contratto #{$contract->id}: {$e->getMessage()}");
            }
        }
        Log::info("BillingCycleJob: {$generated} fatture generate");

        // ── 2. SDD batch per fatture in scadenza oggi (due_date = oggi) ─────
        $sddInvoices = Invoice::whereDate('due_date', $today)
            ->where('status', InvoiceStatus::Issued->value)
            ->where('payment_method', 'sdd')
            ->with(['customer'])
            ->get();

        if ($sddInvoices->isNotEmpty()) {
            try {
                $sepaFile = $sddService->generateBatch($sddInvoices);
                Log::info("BillingCycleJob: SDD batch generato — SepaFile #{$sepaFile->id}, {$sddInvoices->count()} transazioni");
            } catch (\Throwable $e) {
                Log::error("BillingCycleJob: errore generazione SDD batch: {$e->getMessage()}");
            }
        }

        // ── 3. Stripe charge per fatture in scadenza oggi ────────────────────
        $stripeInvoices = Invoice::whereDate('due_date', $today)
            ->where('status', InvoiceStatus::Issued->value)
            ->whereIn('payment_method', ['stripe', 'carta'])
            ->with(['customer'])
            ->get();

        foreach ($stripeInvoices as $invoice) {
            ProcessStripeChargeJob::dispatch($invoice);
        }

        // ── 4. Marca come scadute le fatture non pagate oltre due_date ───────
        $overdueCount = 0;
        Invoice::where('due_date', '<', $today)
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::SentSdi->value])
            ->chunk(200, function ($invoices) use ($invoiceService, &$overdueCount) {
                foreach ($invoices as $invoice) {
                    $invoiceService->markOverdue($invoice);
                    InvoiceOverdue::dispatch($invoice, $invoice->daysOverdue());
                    $overdueCount++;
                }
            });

        if ($overdueCount > 0) {
            Log::warning("BillingCycleJob: {$overdueCount} fatture marcate come scadute");
        }

        Log::info("BillingCycleJob: completato");
    }
}
