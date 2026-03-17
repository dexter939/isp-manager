<?php

declare(strict_types=1);

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Enums\PaymentStatus;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\Payment;
use Modules\Billing\Services\StripeService;
use Tests\TestCase;

/**
 * Test del webhook Stripe con payload reali.
 *
 * Copre:
 *   - payment_intent.succeeded → fattura marcata pagata
 *   - payment_intent.payment_failed → payment marcato failed
 *   - Firma HMAC non valida → 400
 *   - Evento sconosciuto → 200 (silenzioso)
 */
class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_secret_key_for_testing';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.carrier_mock'              => false,
            'services.stripe.webhook_secret' => $this->webhookSecret,
        ]);
    }

    /** @test */
    public function payment_intent_succeeded_marks_invoice_paid(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'issued', 'total' => '49.90']);
        $payment = Payment::factory()->create([
            'invoice_id'               => $invoice->id,
            'stripe_payment_intent_id' => 'pi_test_succeeded_001',
            'status'                   => PaymentStatus::Pending->value,
            'amount'                   => '49.90',
        ]);

        $payload   = $this->buildStripeEvent('payment_intent.succeeded', 'pi_test_succeeded_001', 4990);
        $signature = $this->buildStripeSignature($payload);

        $response = $this->postJson('/api/v1/billing/webhooks/stripe', [], [
            'Stripe-Signature' => $signature,
            'Content-Type'     => 'application/json',
        ])->withBody($payload, 'application/json');

        // In alternativa con call():
        $response = $this->call('POST', '/api/v1/billing/webhooks/stripe',
            [], [], [], ['HTTP_Stripe-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => 'paid',
        ]);
    }

    /** @test */
    public function payment_intent_failed_marks_payment_failed(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'issued', 'total' => '29.90']);
        $payment = Payment::factory()->create([
            'invoice_id'               => $invoice->id,
            'stripe_payment_intent_id' => 'pi_test_failed_001',
            'status'                   => PaymentStatus::Pending->value,
        ]);

        $payload   = $this->buildStripeEvent('payment_intent.payment_failed', 'pi_test_failed_001', 2990);
        $signature = $this->buildStripeSignature($payload);

        $this->call('POST', '/api/v1/billing/webhooks/stripe',
            [], [], [], ['HTTP_Stripe-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => PaymentStatus::Failed->value,
        ]);
    }

    /** @test */
    public function invalid_signature_returns_400(): void
    {
        $payload = $this->buildStripeEvent('payment_intent.succeeded', 'pi_test_sig_001', 1000);

        $response = $this->call('POST', '/api/v1/billing/webhooks/stripe',
            [], [], [], ['HTTP_Stripe-Signature' => 'v1=invalidsignature', 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /** @test */
    public function unknown_event_type_returns_200_silently(): void
    {
        $payload   = $this->buildStripeEvent('customer.created', null, 0);
        $signature = $this->buildStripeSignature($payload);

        $response = $this->call('POST', '/api/v1/billing/webhooks/stripe',
            [], [], [], ['HTTP_Stripe-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function payment_not_found_does_not_throw(): void
    {
        $payload   = $this->buildStripeEvent('payment_intent.succeeded', 'pi_unknown_9999', 1000);
        $signature = $this->buildStripeSignature($payload);

        // Non deve lanciare eccezioni — solo loggare
        $response = $this->call('POST', '/api/v1/billing/webhooks/stripe',
            [], [], [], ['HTTP_Stripe-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildStripeEvent(string $type, ?string $paymentIntentId, int $amountCents): string
    {
        $object = [
            'id'       => $paymentIntentId ?? 'evt_' . uniqid(),
            'object'   => 'payment_intent',
            'amount'   => $amountCents,
            'currency' => 'eur',
            'status'   => str_ends_with($type, 'succeeded') ? 'succeeded' : 'requires_payment_method',
            'metadata' => [],
        ];

        return json_encode([
            'id'      => 'evt_' . uniqid(),
            'object'  => 'event',
            'type'    => $type,
            'created' => time(),
            'livemode' => false,
            'data'    => ['object' => $object],
        ]);
    }

    /**
     * Genera una firma Stripe valida (HMAC-SHA256) per il payload.
     * Replica la logica di \Stripe\Webhook::constructEvent().
     */
    private function buildStripeSignature(string $payload): string
    {
        $timestamp = time();
        $signed    = $timestamp . '.' . $payload;
        $hash      = hash_hmac('sha256', $signed, $this->webhookSecret);

        return "t={$timestamp},v1={$hash}";
    }
}
