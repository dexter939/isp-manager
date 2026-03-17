<?php

declare(strict_types=1);

namespace Modules\Billing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Events\PaymentReceived;
use Modules\Billing\Enums\PaymentStatus;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Services\InvoiceService;
use Modules\Billing\Services\StripeService;

/**
 * Addebita una fattura su Stripe in modo asincrono.
 */
class ProcessStripeChargeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public array $backoff = [30, 300, 1800]; // 30s, 5min, 30min

    public function __construct(
        private readonly Invoice $invoice,
    ) {}

    public function handle(StripeService $stripeService, InvoiceService $invoiceService): void
    {
        $customer = $this->invoice->customer;

        if (!$customer->stripe_customer_id || !$customer->stripe_payment_method_id) {
            Log::warning("ProcessStripeChargeJob: nessun metodo Stripe per customer #{$customer->id}");
            return;
        }

        $payment = $stripeService->chargeInvoice(
            $this->invoice,
            $customer->stripe_customer_id,
            $customer->stripe_payment_method_id,
        );

        if ($payment->isCompleted()) {
            $invoiceService->markPaid($this->invoice, 'stripe', $payment->stripe_payment_intent_id);
            PaymentReceived::dispatch($payment, $this->invoice);
            Log::info("Stripe charge OK: invoice #{$this->invoice->number}");
        } else {
            Log::warning("Stripe charge fallito: invoice #{$this->invoice->number} — {$payment->stripe_error}");
        }
    }
}
