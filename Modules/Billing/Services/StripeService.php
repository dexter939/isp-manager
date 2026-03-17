<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\Payment;
use Modules\Billing\Enums\PaymentStatus;
use Stripe\Exception\CardException;
use Stripe\StripeClient;

/**
 * Gestisce i pagamenti con carta tramite Stripe.
 *
 * Usa PaymentIntent con confirm automatico.
 * In CARRIER_MOCK=true logga senza chiamare Stripe.
 */
class StripeService
{
    private bool $isMocked;
    private ?StripeClient $client = null;

    public function __construct()
    {
        $this->isMocked = (bool) config('app.carrier_mock', false);
    }

    /**
     * Crea e conferma un PaymentIntent per la fattura.
     *
     * @return Payment
     */
    public function chargeInvoice(Invoice $invoice, string $stripeCustomerId, string $paymentMethodId): Payment
    {
        $amountCents = (int) bcmul((string) $invoice->total, '100', 0);

        if ($this->isMocked) {
            Log::info("[MOCK] Stripe charge invoice #{$invoice->id} €{$invoice->total}");
            return $this->createPaymentRecord($invoice, 'mock_pi_' . uniqid(), PaymentStatus::Completed);
        }

        try {
            $intent = $this->stripe()->paymentIntents->create([
                'amount'               => $amountCents,
                'currency'             => 'eur',
                'customer'             => $stripeCustomerId,
                'payment_method'       => $paymentMethodId,
                'confirm'              => true,
                'off_session'          => true,
                'description'          => "Fattura {$invoice->number} — contratto #{$invoice->contract_id}",
                'metadata'             => [
                    'invoice_id'  => $invoice->id,
                    'contract_id' => $invoice->contract_id,
                    'tenant_id'   => $invoice->tenant_id,
                ],
            ]);

            $status = $intent->status === 'succeeded'
                ? PaymentStatus::Completed
                : PaymentStatus::Failed;

            return $this->createPaymentRecord($invoice, $intent->id, $status, $intent->latest_charge);

        } catch (CardException $e) {
            Log::warning("Stripe card error invoice #{$invoice->id}: {$e->getMessage()}");

            $payment = $this->createPaymentRecord($invoice, null, PaymentStatus::Failed);
            $payment->update(['stripe_error' => $e->getMessage()]);

            return $payment;
        }
    }

    /**
     * Crea o recupera il customer Stripe per questo cliente.
     */
    public function ensureStripeCustomer(int $customerId, string $email, string $name): string
    {
        if ($this->isMocked) {
            return 'cus_mock_' . $customerId;
        }

        $customers = $this->stripe()->customers->search([
            'query' => "metadata['customer_id']:'{$customerId}'",
        ]);

        if (count($customers->data) > 0) {
            return $customers->data[0]->id;
        }

        $customer = $this->stripe()->customers->create([
            'email'    => $email,
            'name'     => $name,
            'metadata' => ['customer_id' => $customerId],
        ]);

        return $customer->id;
    }

    /**
     * Gestisce il webhook Stripe (payment_intent.succeeded / payment_intent.payment_failed).
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        if ($this->isMocked) {
            return ['handled' => true];
        }

        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );

        return match($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => ['handled' => false, 'event' => $event->type],
        };
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function handlePaymentSucceeded(object $intent): array
    {
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();
        if (!$payment) {
            return ['handled' => false, 'reason' => 'payment not found'];
        }

        $payment->update([
            'status'       => PaymentStatus::Completed->value,
            'processed_at' => now(),
        ]);

        // Aggiorna fattura
        app(InvoiceService::class)->markPaid($payment->invoice, 'stripe', $intent->id);

        return ['handled' => true, 'payment_id' => $payment->id];
    }

    private function handlePaymentFailed(object $intent): array
    {
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();
        if (!$payment) {
            return ['handled' => false];
        }

        $payment->update([
            'status'       => PaymentStatus::Failed->value,
            'stripe_error' => $intent->last_payment_error?->message,
            'processed_at' => now(),
        ]);

        return ['handled' => true, 'payment_id' => $payment->id];
    }

    private function createPaymentRecord(
        Invoice $invoice,
        ?string $intentId,
        PaymentStatus $status,
        ?string $chargeId = null,
    ): Payment {
        return Payment::create([
            'tenant_id'                => $invoice->tenant_id,
            'invoice_id'               => $invoice->id,
            'customer_id'              => $invoice->customer_id,
            'method'                   => 'stripe',
            'amount'                   => $invoice->total,
            'currency'                 => 'EUR',
            'status'                   => $status->value,
            'stripe_payment_intent_id' => $intentId,
            'stripe_charge_id'         => $chargeId,
            'processed_at'             => $status === PaymentStatus::Completed ? now() : null,
        ]);
    }

    private function stripe(): StripeClient
    {
        if (!$this->client) {
            $this->client = new StripeClient(config('services.stripe.secret'));
        }
        return $this->client;
    }
}
