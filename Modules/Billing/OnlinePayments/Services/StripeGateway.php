<?php

namespace Modules\Billing\OnlinePayments\Services;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Invoice;
use Modules\Billing\OnlinePayments\Events\PaymentMethodAdded;
use Modules\Billing\OnlinePayments\Events\PaymentTransactionCompleted;
use Modules\Billing\OnlinePayments\Models\OnlinePaymentMethod;
use Modules\Billing\OnlinePayments\Models\OnlinePaymentTransaction;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Creates a Stripe PaymentLink for one-time invoice payment.
     * Returns the payment link URL.
     */
    public function createPaymentLink(object $customer, Invoice $invoice): string
    {
        if (config('app.carrier_mock', false)) {
            return 'https://buy.stripe.com/mock_' . $invoice->id;
        }

        $price = $this->stripe->prices->create([
            'unit_amount' => $invoice->total_cents,
            'currency'    => config('online_payments.stripe.currency', 'eur'),
            'product_data'=> ['name' => 'Fattura ' . $invoice->number],
        ]);

        $link = $this->stripe->paymentLinks->create([
            'line_items' => [['price' => $price->id, 'quantity' => 1]],
            'metadata'   => ['invoice_id' => $invoice->id],
        ]);

        return $link->url;
    }

    /**
     * Creates SetupIntent for recurring off-session payments.
     * Returns client_secret for frontend confirmation.
     */
    public function createRecurringSetup(object $customer): array
    {
        if (config('app.carrier_mock', false)) {
            return ['setup_intent_id' => 'seti_mock', 'client_secret' => 'mock_secret'];
        }

        $stripeCustomer = $this->stripe->customers->create([
            'email'    => $customer->email,
            'metadata' => ['customer_id' => $customer->id],
        ]);

        $setupIntent = $this->stripe->setupIntents->create([
            'customer'             => $stripeCustomer->id,
            'usage'                => 'off_session',
            'payment_method_types' => ['card'],
        ]);

        return [
            'setup_intent_id' => $setupIntent->id,
            'client_secret'   => $setupIntent->client_secret,
        ];
    }

    /**
     * Charges a stored recurring payment method (MIT).
     */
    public function chargeRecurring(OnlinePaymentMethod $method, Invoice $invoice): OnlinePaymentTransaction
    {
        $transaction = OnlinePaymentTransaction::create([
            'payment_method_id'       => $method->id,
            'invoice_id'              => $invoice->id,
            'gateway'                 => 'stripe',
            'external_transaction_id' => 'pi_mock_' . $invoice->id,
            'amount_cents'            => $invoice->total_cents,
            'currency'                => 'EUR',
            'status'                  => 'pending',
            'is_recurring'            => true,
        ]);

        if (config('app.carrier_mock', false)) {
            $transaction->update(['status' => 'succeeded', 'external_transaction_id' => 'pi_mock_' . $invoice->id]);
            event(new PaymentTransactionCompleted($transaction));
            return $transaction;
        }

        $intent = $this->stripe->paymentIntents->create([
            'amount'               => $invoice->total_cents,
            'currency'             => config('online_payments.stripe.currency', 'eur'),
            'customer'             => $method->external_customer_id,
            'payment_method'       => $method->external_method_id,
            'off_session'          => true,
            'confirm'              => true,
        ]);

        $status = $intent->status === 'succeeded' ? 'succeeded' : 'failed';
        $transaction->update(['status' => $status, 'external_transaction_id' => $intent->id]);

        if ($status === 'succeeded') {
            event(new PaymentTransactionCompleted($transaction));
        }

        return $transaction;
    }

    /**
     * Handles Stripe webhook events with HMAC validation.
     */
    public function handleWebhook(string $payload, string $sigHeader): void
    {
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, config('online_payments.stripe.webhook_secret'));
        } catch (SignatureVerificationException $e) {
            throw new \InvalidArgumentException('Invalid Stripe webhook signature', 0, $e);
        }

        match ($event->type) {
            'payment_intent.succeeded'   => $this->onPaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->onPaymentFailed($event->data->object),
            'setup_intent.succeeded'     => $this->onSetupIntentSucceeded($event->data->object),
            default                      => Log::info('Unhandled Stripe event: ' . $event->type),
        };
    }

    private function onPaymentSucceeded(object $paymentIntent): void
    {
        $transaction = OnlinePaymentTransaction::where('external_transaction_id', $paymentIntent->id)->first();
        if ($transaction) {
            $transaction->update(['status' => 'succeeded']);
            event(new PaymentTransactionCompleted($transaction));
        }
    }

    private function onPaymentFailed(object $paymentIntent): void
    {
        OnlinePaymentTransaction::where('external_transaction_id', $paymentIntent->id)
            ->update(['status' => 'failed']);
    }

    private function onSetupIntentSucceeded(object $setupIntent): void
    {
        if (!$setupIntent->payment_method || !$setupIntent->customer) {
            return;
        }

        $pm = $this->stripe->paymentMethods->retrieve($setupIntent->payment_method);

        OnlinePaymentMethod::create([
            'customer_id'          => $setupIntent->metadata['customer_id'] ?? null,
            'gateway'              => 'stripe',
            'external_customer_id' => $setupIntent->customer,
            'external_method_id'   => $setupIntent->payment_method,
            'card_brand'           => $pm->card?->brand,
            'card_last4'           => $pm->card?->last4,
            'card_expiry'          => sprintf('%02d/%s', $pm->card?->exp_month, substr((string) $pm->card?->exp_year, -2)),
        ]);

        event(new PaymentMethodAdded(OnlinePaymentMethod::latest()->first()));
    }
}
