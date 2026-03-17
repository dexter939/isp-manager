<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Enums\DunningAction;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Enums\InvoiceType;
use Modules\Billing\Models\AgentCommission;
use Modules\Billing\Models\DunningStep;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\InvoiceItem;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Enums\BillingCycle;

/**
 * Gestisce la generazione e il ciclo di vita delle fatture.
 *
 * Flusso:
 * 1. generateForContract()  → crea fattura Draft
 * 2. issue()                → emette (Draft → Issued), genera PDF, crea dunning steps
 * 3. markPaid()             → segna come pagata, registra provvigioni
 * 4. markOverdue()          → segna come scaduta (schedulato da BillingCycleJob)
 */
class InvoiceService
{
    /**
     * Genera la fattura per il periodo corrente di un contratto attivo.
     * Chiamato da BillingCycleJob ogni giorno.
     *
     * @return Invoice
     */
    public function generateForContract(Contract $contract): Invoice
    {
        $contract->load(['customer', 'servicePlan', 'agent']);

        [$periodFrom, $periodTo] = $this->computePeriod($contract);
        $issueDate = Carbon::today();
        $dueDate   = $issueDate->copy()->addDays(5); // D+5 = data addebito SDD/Stripe

        return DB::transaction(function () use ($contract, $periodFrom, $periodTo, $issueDate, $dueDate) {
            $invoice = Invoice::create([
                'tenant_id'      => $contract->tenant_id,
                'customer_id'    => $contract->customer_id,
                'contract_id'    => $contract->id,
                'agent_id'       => $contract->agent_id,
                'number'         => $this->nextInvoiceNumber($contract->tenant_id),
                'type'           => InvoiceType::Fattura->value,
                'period_from'    => $periodFrom,
                'period_to'      => $periodTo,
                'issue_date'     => $issueDate,
                'due_date'       => $dueDate,
                'status'         => InvoiceStatus::Draft->value,
                'subtotal'       => 0,
                'tax_rate'       => 22.00,
                'tax_amount'     => 0,
                'stamp_duty'     => 0,
                'total'          => 0,
            ]);

            $isFirst = $this->isFirstInvoice($contract);

            $this->addLineItem($invoice, (string) $contract->monthly_price,
                "Canone internet — piano {$contract->servicePlan->name}", 'canone', 10, $periodFrom, $periodTo);

            if ($contract->activation_fee > 0 && $isFirst) {
                $this->addLineItem($invoice, (string) $contract->activation_fee,
                    'Costo di attivazione (una tantum)', 'attivazione', 20);
            }

            if ($contract->modem_fee > 0 && $isFirst) {
                $this->addLineItem($invoice, (string) $contract->modem_fee,
                    'Contributo modem/ONT', 'modem', 30);
            }

            $this->recalculateTotals($invoice);

            return $invoice->fresh();
        });
    }

    /**
     * Emette la fattura: Draft → Issued.
     * Genera il PDF, il file XML SDI e pianifica i dunning steps.
     */
    public function issue(Invoice $invoice): Invoice
    {
        $this->assertTransition($invoice, InvoiceStatus::Issued);

        return DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => InvoiceStatus::Issued->value]);

            // Pianifica il ciclo di dunning
            $this->scheduleDunningSteps($invoice);

            // Pianifica la generazione del PDF (via Job asincrono)
            // GenerateInvoicePdfJob::dispatch($invoice);

            // Crea la provvigione di attivazione se è la prima fattura
            if ($invoice->agent_id && $this->isFirstInvoice($invoice->contract)) {
                $this->accrueActivationCommission($invoice);
            }

            return $invoice->fresh();
        });
    }

    /**
     * Registra il pagamento e aggiorna lo stato a Paid.
     */
    public function markPaid(Invoice $invoice, string $method, ?string $reference = null): Invoice
    {
        $this->assertTransition($invoice, InvoiceStatus::Paid);

        $invoice->update([
            'status'         => InvoiceStatus::Paid->value,
            'paid_at'        => now(),
            'payment_method' => $method,
        ]);

        // Cancella i dunning steps pendenti
        $invoice->dunningSteps()
            ->where('status', 'pending')
            ->update(['status' => 'skipped']);

        // Accredita provvigione ricorrente agente
        if ($invoice->agent_id) {
            $this->accrueRecurringCommission($invoice);
        }

        return $invoice->fresh();
    }

    /**
     * Segna la fattura come scaduta (chiamato da BillingCycleJob).
     */
    public function markOverdue(Invoice $invoice): void
    {
        if (!$invoice->status->isPayable()) {
            return;
        }
        $invoice->update(['status' => InvoiceStatus::Overdue->value]);
    }

    /**
     * Annulla la fattura (emette nota di credito se già inviata a SDI).
     */
    public function cancel(Invoice $invoice, string $reason = ''): Invoice
    {
        $this->assertTransition($invoice, InvoiceStatus::Cancelled);

        $invoice->update([
            'status' => InvoiceStatus::Cancelled->value,
            'notes'  => array_merge($invoice->notes ?? [], ['cancellation_reason' => $reason]),
        ]);

        $invoice->dunningSteps()->where('status', 'pending')->update(['status' => 'skipped']);

        return $invoice->fresh();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function addLineItem(
        Invoice $invoice,
        string $net,
        string $description,
        string $type,
        int $sortOrder,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): void {
        $totals = InvoiceItem::computeTotals(1, $net, '22.00');

        $data = [
            'invoice_id'  => $invoice->id,
            'description' => $description,
            'type'        => $type,
            'quantity'    => 1,
            'unit_price'  => $net,
            'tax_rate'    => '22.00',
            'sort_order'  => $sortOrder,
            ...$totals,
        ];

        if ($from !== null) {
            $data['period_from'] = $from;
            $data['period_to']   = $to;
        }

        InvoiceItem::create($data);
    }

    private function recalculateTotals(Invoice $invoice): void
    {
        // Reload items — created by addLineItem() moments ago, not yet in the model's collection
        $invoice->load('items');

        $subtotal = '0';
        $taxTotal = '0';
        $stampDuty = '0';

        foreach ($invoice->items as $item) {
            $subtotal = bcadd($subtotal, (string) $item->total_net, 2);
            $taxTotal = bcadd($taxTotal, (string) $item->total_tax, 2);
        }

        // Bollo virtuale €2.00 se imponibile esente/fuori campo > €77.47
        // (semplificato: nessun bollo per servizi TLC standard)

        $total = bcadd(bcadd($subtotal, $taxTotal, 2), $stampDuty, 2);

        $invoice->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxTotal,
            'stamp_duty' => $stampDuty,
            'total'      => $total,
        ]);
    }

    /**
     * Pianifica i passi di dunning per questa fattura.
     * Giorno 0 = due_date (data scadenza/addebito).
     */
    private function scheduleDunningSteps(Invoice $invoice): void
    {
        $base = $invoice->due_date instanceof Carbon
            ? $invoice->due_date
            : Carbon::parse($invoice->due_date);

        $steps = [
            ['step' => 1, 'action' => DunningAction::EmailReminder],
            ['step' => 2, 'action' => DunningAction::SmsReminder],
            ['step' => 3, 'action' => DunningAction::WhatsAppReminder],
            ['step' => 4, 'action' => DunningAction::Suspension],
            ['step' => 5, 'action' => DunningAction::RetrySdd],
            ['step' => 6, 'action' => DunningAction::Termination],
        ];

        $now  = now();
        $rows = [];

        foreach ($steps as $s) {
            $rows[] = [
                'tenant_id'    => $invoice->tenant_id,
                'invoice_id'   => $invoice->id,
                'customer_id'  => $invoice->customer_id,
                'contract_id'  => $invoice->contract_id,
                'step'         => $s['step'],
                'action'       => $s['action']->value,
                'status'       => 'pending',
                'scheduled_at' => $base->copy()->addDays($s['action']->dayOffset()),
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        DunningStep::insert($rows);
    }

    private function accrueActivationCommission(Invoice $invoice): void
    {
        $this->accrueCommission($invoice, 'activation', (string) $invoice->contract->activation_fee, '0.2000');
    }

    private function accrueRecurringCommission(Invoice $invoice): void
    {
        $this->accrueCommission($invoice, 'recurring', (string) $invoice->subtotal, '0.0500');
    }

    private function accrueCommission(Invoice $invoice, string $type, string $base, string $rate): void
    {
        AgentCommission::create([
            'tenant_id'   => $invoice->tenant_id,
            'agent_id'    => $invoice->agent_id,
            'contract_id' => $invoice->contract_id,
            'invoice_id'  => $invoice->id,
            'type'        => $type,
            'base_amount' => $base,
            'rate'        => $rate,
            'amount'      => bcmul($base, $rate, 2),
            'currency'    => 'EUR',
            'status'      => 'accrued',
            'accrued_on'  => today(),
        ]);
    }

    private function isFirstInvoice(Contract $contract): bool
    {
        return !Invoice::where('contract_id', $contract->id)
            ->whereNot('status', InvoiceStatus::Cancelled->value)
            ->exists();
    }

    /**
     * Calcola period_from e period_to in base al billing_cycle del contratto.
     *
     * @return array{Carbon, Carbon}
     */
    private function computePeriod(Contract $contract): array
    {
        $billingDay = $contract->billing_day ?? 1;
        $today = Carbon::today();

        $from = Carbon::createFromDate($today->year, $today->month, $billingDay);
        if ($from->isFuture()) {
            $from->subMonthNoOverflow();
        }

        $to = match($contract->billing_cycle) {
            BillingCycle::Monthly => $from->copy()->addMonthNoOverflow()->subDay(),
            BillingCycle::Annual  => $from->copy()->addYear()->subDay(),
        };

        return [$from, $to];
    }

    /**
     * Genera il prossimo numero fattura progressivo per il tenant.
     * Formato: YYYY/NNNNNN (es. 2024/000042)
     */
    private function nextInvoiceNumber(int $tenantId): string
    {
        $year = now()->year;

        $last = Invoice::where('tenant_id', $tenantId)
            ->whereYear('issue_date', $year)
            ->whereNot('status', InvoiceStatus::Draft->value)
            ->lockForUpdate()
            ->max(DB::raw("CAST(SPLIT_PART(number, '/', 2) AS INTEGER)"));

        $next = ($last ?? 0) + 1;

        return sprintf('%d/%06d', $year, $next);
    }

    private function assertTransition(Invoice $invoice, InvoiceStatus $target): void
    {
        if (!$invoice->status->canTransitionTo($target)) {
            throw new \DomainException(
                "Impossibile passare da [{$invoice->status->value}] a [{$target->value}] per fattura #{$invoice->id}"
            );
        }
    }
}
