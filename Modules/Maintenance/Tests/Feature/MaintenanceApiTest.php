<?php

declare(strict_types=1);

namespace Modules\Maintenance\Tests\Feature;

use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;
use Modules\Maintenance\Models\InventoryItem;
use Modules\Maintenance\Models\TroubleTicket;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MaintenanceApiTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeUser(tenantId: 1, role: 'admin');
    }

    // ── Tickets ───────────────────────────────────────────────────────────────

    /** @test */
    public function it_lists_tickets_for_tenant(): void
    {
        TroubleTicket::factory()->count(3)->create(['tenant_id' => 1]);
        TroubleTicket::factory()->count(2)->create(['tenant_id' => 2]); // altro tenant

        $this->actingAs($this->admin)
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_creates_ticket_via_api(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/tickets', [
                'title'       => 'Internet non funziona',
                'description' => 'Nessuna connettività da stamattina',
                'priority'    => 'high',
                'type'        => 'assurance',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('trouble_tickets', [
            'title'    => 'Internet non funziona',
            'status'   => 'open',
            'priority' => 'high',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_ticket(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/tickets', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description', 'priority']);
    }

    /** @test */
    public function it_resolves_ticket(): void
    {
        $ticket = TroubleTicket::factory()->create([
            'tenant_id' => 1,
            'status'    => TicketStatus::InProgress->value,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/tickets/{$ticket->id}/resolve", [
                'resolution_notes' => 'ONT resettato, linea ripristinata',
            ])
            ->assertOk();

        $this->assertEquals(TicketStatus::Resolved, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->resolved_at);
    }

    /** @test */
    public function it_adds_note_to_ticket(): void
    {
        $ticket = TroubleTicket::factory()->create(['tenant_id' => 1]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/tickets/{$ticket->id}/notes", [
                'body'        => 'Tecnico in arrivo domani mattina',
                'is_internal' => false,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('ticket_notes', [
            'ticket_id'   => $ticket->id,
            'body'        => 'Tecnico in arrivo domani mattina',
            'is_internal' => false,
        ]);
    }

    /** @test */
    public function it_rejects_cross_tenant_ticket_access(): void
    {
        $ticket = TroubleTicket::factory()->create(['tenant_id' => 99]);

        $this->actingAs($this->admin)
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
    }

    // ── Inventory ─────────────────────────────────────────────────────────────

    /** @test */
    public function it_lists_inventory_for_tenant(): void
    {
        InventoryItem::factory()->count(4)->create(['tenant_id' => 1]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/inventory')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }

    /** @test */
    public function it_returns_low_stock_items(): void
    {
        InventoryItem::factory()->create(['tenant_id' => 1, 'quantity' => 2, 'reorder_threshold' => 5]);
        InventoryItem::factory()->create(['tenant_id' => 1, 'quantity' => 10, 'reorder_threshold' => 5]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/inventory/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_creates_inventory_item(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/inventory', [
                'sku'      => 'ONT-NOKIA-XS-010X-Q',
                'name'     => 'Nokia ONT XS-010X-Q',
                'category' => 'ont',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('inventory_items', ['sku' => 'ONT-NOKIA-XS-010X-Q']);
    }

    /** @test */
    public function it_receives_stock(): void
    {
        $item = InventoryItem::factory()->create(['tenant_id' => 1, 'quantity' => 0]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/inventory/{$item->id}/receive", [
                'quantity'  => 50,
                'reference' => 'DDT-2024-001',
            ])
            ->assertCreated();

        $this->assertEquals(50, $item->fresh()->quantity);
    }
}
