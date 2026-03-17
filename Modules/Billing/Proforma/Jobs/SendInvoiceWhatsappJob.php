<?php
namespace Modules\Billing\Proforma\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class SendInvoiceWhatsappJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $backoff = 60;
    public function __construct(
        private readonly string $invoiceId,
        private readonly string $pdfPath,
    ) {}
    public function handle(): void {
        $invoice  = DB::table('invoices')->find($this->invoiceId);
        if (!$invoice) { $this->fail(new \RuntimeException("Invoice {$this->invoiceId} not found")); return; }
        $contract = DB::table('contracts')->find($invoice->contract_id);
        if (!$contract || !$contract->invoice_whatsapp_enabled) { return; }
        $customer = DB::table('customers')->find($invoice->customer_id);
        $phone    = $contract->invoice_whatsapp_number ?? $customer?->mobile;
        if (!$phone) { Log::info("SendInvoiceWhatsappJob: no WhatsApp number for invoice {$this->invoiceId}, skipping"); return; }
        try {
            if (config('app.carrier_mock', false)) { Log::info("MOCK: WhatsApp sent to {$phone} with PDF {$this->pdfPath}"); return; }
            // Use WhatsApp service from CustomerCare module
            $whatsapp = app(\Modules\AI\Services\WhatsAppService::class);
            $message  = "Gentile {$customer->name}, in allegato la sua fattura n. {$invoice->number} di importo " . number_format($invoice->amount_cents / 100, 2, ',', '.') . " EUR. Scadenza: {$invoice->due_date}.";
            $whatsapp->sendTemplate($phone, 'invoice_notification', ['name'=>$customer->name,'number'=>$invoice->number,'amount'=>number_format($invoice->amount_cents/100,2,',','.'),'due_date'=>$invoice->due_date]);
        } catch (\Throwable $e) {
            Log::error("SendInvoiceWhatsappJob failed for {$this->invoiceId}: {$e->getMessage()}");
            if ($this->attempts() >= $this->tries) {
                Log::warning("SendInvoiceWhatsappJob: fallback to email for invoice {$this->invoiceId}");
                // Fallback: send email
                Mail::raw("Fattura {$invoice->number} allegata.", fn($m) => $m->to($customer?->email)->subject("Fattura {$invoice->number}"));
            }
            throw $e;
        }
    }
}
