<?php

declare(strict_types=1);

namespace Modules\Billing\Tests\Feature;

use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Services\InvoiceService;
use Modules\Contracts\Enums\ContractStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BillingApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function billing_admin_can_list_invoices(): void
    {
        $user = $this->makeBillingUser();
        Invoice::factory()->count(3)->create(['tenant_id' => $user->tenant_id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/invoices');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function agent_can_only_see_own_invoices(): void
    {
        $agent = $this->makeAgentUser();
        Invoice::factory()->count(2)->create([
            'tenant_id' => $agent->tenant_id,
            'agent_id'  => $agent->id,
        ]);
        Invoice::factory()->count(5)->create([
            'tenant_id' => $agent->tenant_id,
            'agent_id'  => 999, // altro agente
        ]);

        $response = $this->actingAs($agent, 'sanctum')
            ->getJson('/api/v1/invoices');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function billing_admin_can_issue_draft_invoice(): void
    {
        $user    = $this->makeBillingUser();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status'    => InvoiceStatus::Draft->value,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/issue");

        $response->assertOk();
        $this->assertEquals(InvoiceStatus::Issued->value, $invoice->fresh()->status->value);
    }

    /** @test */
    public function billing_admin_can_mark_invoice_as_paid(): void
    {
        $user    = $this->makeBillingUser();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status'    => InvoiceStatus::Issued->value,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/mark-paid", [
                'payment_method' => 'bonifico',
                'reference'      => 'CRO12345678',
            ]);

        $response->assertOk();
        $this->assertEquals(InvoiceStatus::Paid->value, $invoice->fresh()->status->value);
    }

    /** @test */
    public function it_returns_404_for_invoice_of_different_tenant(): void
    {
        $user    = $this->makeBillingUser();
        $invoice = Invoice::factory()->create(['tenant_id' => 999]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_invoices(): void
    {
        $this->getJson('/api/v1/invoices')
            ->assertUnauthorized();
    }

    /** @test */
    public function billing_admin_can_cancel_issued_invoice(): void
    {
        $user    = $this->makeBillingUser();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status'    => InvoiceStatus::Issued->value,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/cancel", [
                'reason' => 'Errore di emissione',
            ])
            ->assertOk();

        $this->assertEquals(InvoiceStatus::Cancelled->value, $invoice->fresh()->status->value);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeBillingUser()
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $user   = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('billing');
        return $user;
    }

    private function makeAgentUser()
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $user   = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('agent');
        return $user;
    }
}
