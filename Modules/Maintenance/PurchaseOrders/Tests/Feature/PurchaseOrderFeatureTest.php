<?php

declare(strict_types=1);

namespace Modules\Maintenance\PurchaseOrders\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Maintenance\PurchaseOrders\Models\PurchaseOrder;
use Tests\TestCase;

class PurchaseOrderFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $supplierId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplierId = $this->createSupplier();
    }

    // ── List ──────────────────────────────────────────────────────────────────

    /** @test */
    public function it_lists_purchase_orders(): void
    {
        $this->actingAsAdmin()->getJson('/api/purchase-orders')->assertOk()->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_lists_suppliers(): void
    {
        $this->actingAsAdmin()->getJson('/api/purchase-orders/suppliers')->assertOk();
    }

    /** @test */
    public function it_lists_reorder_rules(): void
    {
        $this->actingAsAdmin()->getJson('/api/purchase-orders/reorder-rules')->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    /** @test */
    public function it_creates_purchase_order_with_items(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/purchase-orders', [
                'supplier_id'       => $this->supplierId,
                'expected_delivery' => now()->addDays(14)->toDateString(),
                'items'             => [
                    ['description' => 'Router CPE X', 'quantity_ordered' => 10, 'unit_price' => 4990, 'sku' => 'CPE-X-01'],
                    ['description' => 'SFP Module',   'quantity_ordered' => 5,  'unit_price' => 2500, 'sku' => 'SFP-LC'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'draft')
            ->assertJsonCount(2, 'items');
    }

    /** @test */
    public function it_validates_purchase_order_requires_items(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/purchase-orders', ['supplier_id' => $this->supplierId])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function it_validates_each_item_requires_description_and_quantity(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $this->supplierId,
                'items'       => [['unit_price' => 100]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.description', 'items.0.quantity_ordered']);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    /** @test */
    public function it_shows_purchase_order_with_items(): void
    {
        $po = PurchaseOrder::factory()->create(['tenant_id' => 1, 'supplier_id' => $this->supplierId]);
        DB::table('purchase_order_items')->insert([
            'id'                => Str::uuid()->toString(),
            'purchase_order_id' => $po->id,
            'description'       => 'Test Item',
            'quantity_ordered'  => 5,
            'quantity_received' => 0,
            'unit_price'        => 1000,
        ]);

        $this->actingAsAdmin()
            ->getJson("/api/purchase-orders/{$po->id}")
            ->assertOk()
            ->assertJsonPath('id', $po->id)
            ->assertJsonStructure(['id', 'status', 'items']);
    }

    // ── Receive workflow ──────────────────────────────────────────────────────

    /** @test */
    public function it_marks_order_as_fully_received(): void
    {
        $po     = PurchaseOrder::factory()->approved()->create(['tenant_id' => 1, 'supplier_id' => $this->supplierId]);
        $itemId = Str::uuid()->toString();
        DB::table('purchase_order_items')->insert([
            'id'                => $itemId,
            'purchase_order_id' => $po->id,
            'description'       => 'CPE Router',
            'quantity_ordered'  => 10,
            'quantity_received' => 0,
            'unit_price'        => 4990,
        ]);

        $this->actingAsAdmin()
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'items' => [['id' => $itemId, 'qty' => 10]],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'received');
    }

    /** @test */
    public function it_marks_order_as_partial_when_partially_received(): void
    {
        $po     = PurchaseOrder::factory()->approved()->create(['tenant_id' => 1, 'supplier_id' => $this->supplierId]);
        $itemId = Str::uuid()->toString();
        DB::table('purchase_order_items')->insert([
            'id'                => $itemId,
            'purchase_order_id' => $po->id,
            'description'       => 'CPE Router',
            'quantity_ordered'  => 10,
            'quantity_received' => 0,
            'unit_price'        => 4990,
        ]);

        $this->actingAsAdmin()
            ->postJson("/api/purchase-orders/{$po->id}/receive", [
                'items' => [['id' => $itemId, 'qty' => 6]],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'partial');
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    /** @test */
    public function it_cancels_draft_order(): void
    {
        $po = PurchaseOrder::factory()->draft()->create(['tenant_id' => 1, 'supplier_id' => $this->supplierId]);

        $this->actingAsAdmin()
            ->postJson("/api/purchase-orders/{$po->id}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    }

    // ── Tenant isolation ──────────────────────────────────────────────────────

    /** @test */
    public function it_does_not_expose_other_tenant_orders(): void
    {
        $otherTenant   = $this->createTenant();
        $otherSupplier = $this->createSupplier($otherTenant);
        PurchaseOrder::factory()->create(['tenant_id' => $otherTenant, 'supplier_id' => $otherSupplier]);

        $response = $this->actingAsAdmin(1)->getJson('/api/purchase-orders')->assertOk();
        $this->assertEmpty(collect($response->json('data'))->pluck('id'));
    }
}
