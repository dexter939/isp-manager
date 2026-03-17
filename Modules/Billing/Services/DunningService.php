<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Enums\DunningAction;
use Modules\Billing\Models\DunningStep;
use Modules\Billing\Models\Invoice;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Contracts\Services\ContractService;
use Modules\Contracts\Services\NotificationService;

/**
 * Esegue il ciclo di dunning (morosità) per le fatture non pagate.
 *
 * Ciclo configurato in CLAUDE_CONTEXT.md:
 * D+0:  Fattura emessa e inviata
 * D+5:  Addebito SDD o charge Stripe
 * D+10: Reminder 1 (email cortese)
 * D+15: Reminder 2 (SMS urgente + email)
 * D+20: Reminder 3 (WhatsApp) + avviso sospensione imminente
 * D+25: SOSPENSIONE → CoA RADIUS Walled Garden
 * D+30: Secondo tentativo SDD (solo AM04)
 * D+45: CESSAZIONE → DeactivationOrder al carrier
 *
 * Chiamato da DunningJob ogni ora.
 */
class DunningService
{
    public function __construct(
        private readonly NotificationService $notifier,
        private readonly InvoiceService $invoiceService,
        private readonly ContractService $contractService,
    ) {}

    /**
     * Processa tutti i dunning steps in scadenza.
     *
     * @return array{executed: int, failed: int}
     */
    public function processScheduledSteps(): array
    {
        $executed = 0;
        $failed   = 0;

        DunningStep::pendingDue()
            ->with(['invoice.customer', 'invoice.contract'])
            ->chunk(100, function ($steps) use (&$executed, &$failed) {
                foreach ($steps as $step) {
                    try {
                        $this->executeStep($step);
                        $executed++;
                    } catch (\Throwable $e) {
                        $step->markFailed($e->getMessage());
                        Log::error("DunningStep #{$step->id} fallito: {$e->getMessage()}");
                        $failed++;
                    }
                }
            });

        return compact('executed', 'failed');
    }

    /**
     * Esegue un singolo dunning step.
     */
    public function executeStep(DunningStep $step): void
    {
        $invoice  = $step->invoice;
        $customer = $invoice->customer;
        $contract = $invoice->contract;

        // Se la fattura è già stata pagata, skippa tutti gli step
        if ($invoice->isPaid()) {
            $step->update(['status' => 'skipped']);
            return;
        }

        $result = match($step->action) {
            DunningAction::EmailReminder    => $this->sendEmailReminder($invoice, $step->step),
            DunningAction::SmsReminder      => $this->sendSmsReminder($invoice, $customer),
            DunningAction::WhatsAppReminder => $this->sendWhatsAppReminder($invoice, $customer),
            DunningAction::Suspension       => $this->suspendContract($contract, $invoice),
            DunningAction::RetrySdd         => $this->retrySdd($invoice),
            DunningAction::Termination      => $this->terminateContract($contract, $invoice),
        };

        $step->markExecuted($result);
    }

    // ── Step handlers ────────────────────────────────────────────────────────

    private function sendEmailReminder(Invoice $invoice, int $step): string
    {
        // TODO Fase 5: invia email tramite Laravel Mail
        Log::info("Dunning D+{$step}: email reminder fattura #{$invoice->number} → {$invoice->customer->email}");
        return "Email reminder inviata a {$invoice->customer->email}";
    }

    private function sendSmsReminder(Invoice $invoice, $customer): string
    {
        $recipient = $customer->cellulare ?? $customer->telefono ?? '';
        $message   = "IMPORTANTE: Fattura {$invoice->number} di €{$invoice->total} non pagata. "
            . "Evita la sospensione: paga entro 5 giorni.";

        $this->notifier->sendSms($recipient, $message);
        return "SMS inviato a {$recipient}";
    }

    private function sendWhatsAppReminder(Invoice $invoice, $customer): string
    {
        $recipient = $customer->cellulare ?? '';
        $this->notifier->sendWhatsApp($recipient, 'dunning_reminder', [
            '1' => $invoice->number,
            '2' => (string) $invoice->total,
            '3' => $invoice->due_date->format('d/m/Y'),
        ]);
        return "WhatsApp inviato a {$recipient}";
    }

    private function suspendContract(Contract $contract, Invoice $invoice): string
    {
        // Sospende il contratto → ContractService aggiorna lo stato
        // Il Network module gestirà il CoA RADIUS (evento ContractSuspended)
        $this->contractService->suspend($contract, reason: "Mancato pagamento fattura {$invoice->number}");

        Log::warning("Sospensione contratto #{$contract->id} per morosità (fattura #{$invoice->number})");
        return "Contratto #{$contract->id} sospeso";
    }

    private function retrySdd(Invoice $invoice): string
    {
        // Solo se il pagamento fallito era AM04 (fondi insufficienti)
        $lastPayment = $invoice->payments()
            ->where('method', 'sdd')
            ->where('sepa_return_code', 'AM04')
            ->latest()
            ->first();

        if (!$lastPayment) {
            return "Nessun pagamento SDD AM04 trovato — step skippato";
        }

        // Crea un nuovo batch SDD solo per questa fattura
        $sepaFile = app(SddService::class)->generateBatch(collect([$invoice]));
        return "Retry SDD generato: SepaFile #{$sepaFile->id}";
    }

    private function terminateContract(Contract $contract, Invoice $invoice): string
    {
        $this->contractService->terminate($contract, reason: "Morosità prolungata — fattura {$invoice->number} non pagata");

        // Il Provisioning module gestirà l'OLO_DeactivationOrder (evento ContractTerminated)
        Log::warning("Cessazione contratto #{$contract->id} per morosità");
        return "Contratto #{$contract->id} cessato";
    }
}
