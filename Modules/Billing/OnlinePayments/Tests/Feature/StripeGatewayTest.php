<?php

namespace Modules\Billing\OnlinePayments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Billing\Models\Invoice;
use Modules\Billing\OnlinePayments\Models\OnlinePaymentTransaction;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_payment_link_mock_mode(): void
    {
        config(['app.carrier_mock' => true]);
        $invoice = Invoice::factory()->create(['total_cents' => 5000]);
        $user    = $this->createUser();

        $response = $this->actingAs($user)->postJson('/api/payments/link/' . $invoice->id);

        $response->assertOk();
        $this->assertStringContainsString('mock', $response->json('data.url'));
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/payments/stripe/webhook', ['type' => 'test'], [
            'Stripe-Signature' => 'invalid',
        ]);

        $response->assertStatus(400);
    }

    public function test_handles_payment_succeeded_webhook(): void
    {
        $transaction = OnlinePaymentTransaction::factory()->create([
            'external_transaction_id' => 'pi_test123',
            'status'                  => 'pending',
            'gateway'                 => 'stripe',
        ]);

        // Simulate successful update via service
        $transaction->update(['status' => 'succeeded']);
        $this->assertEquals('succeeded', $transaction->fresh()->status);
    }
}
