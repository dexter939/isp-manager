<?php

declare(strict_types=1);

namespace Modules\Maintenance\Tests\Unit;

use Modules\Maintenance\Enums\InventoryMovementType;
use Modules\Maintenance\Models\InventoryItem;
use Modules\Maintenance\Services\InventoryService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    private function makeItem(int $quantity = 0, int $threshold = 0): InventoryItem
    {
        return InventoryItem::create([
            'tenant_id'         => 1,
            'sku'               => 'ONT-' . uniqid(),
            'name'              => 'ONT Test',
            'category'          => 'ont',
            'quantity'          => $quantity,
            'quantity_reserved' => 0,
            'reorder_threshold' => $threshold,
            'is_active'         => true,
        ]);
    }

    /** @test */
    public function it_receives_stock(): void
    {
        $item = $this->makeItem(quantity: 10);
        $movement = $this->service->receive($item, 5, userId: 1, reference: 'DDT-001');

        $this->assertEquals(15, $item->fresh()->quantity);
        $this->assertEquals(InventoryMovementType::In, $movement->type);
        $this->assertEquals(5, $movement->quantity);
        $this->assertEquals(10, $movement->quantity_before);
        $this->assertEquals(15, $movement->quantity_after);
    }

    /** @test */
    public function it_consumes_stock(): void
    {
        $item = $this->makeItem(quantity: 10);
        $movement = $this->service->consume($item, 3, userId: 1);

        $this->assertEquals(7, $item->fresh()->quantity);
        $this->assertEquals(-3, $movement->quantity);
    }

    /** @test */
    public function it_throws_when_insufficient_stock(): void
    {
        $item = $this->makeItem(quantity: 2);

        $this->expectException(\RuntimeException::class);

        $this->service->consume($item, 5, userId: 1);
    }

    /** @test */
    public function it_adjusts_stock_to_exact_quantity(): void
    {
        $item = $this->makeItem(quantity: 10);
        $movement = $this->service->adjust($item, 7, userId: 1);

        $this->assertEquals(7, $item->fresh()->quantity);
        $this->assertEquals(InventoryMovementType::Adjustment, $movement->type);
        $this->assertEquals(-3, $movement->quantity);
    }

    /** @test */
    public function it_reserves_and_releases_stock(): void
    {
        $item = $this->makeItem(quantity: 10);
        $this->service->reserve($item, 4);

        $this->assertEquals(4, $item->fresh()->quantity_reserved);
        $this->assertEquals(6, $item->fresh()->availableQuantity());

        $this->service->releaseReservation($item->fresh(), 4);
        $this->assertEquals(0, $item->fresh()->quantity_reserved);
    }

    /** @test */
    public function it_throws_when_reserve_exceeds_available(): void
    {
        $item = $this->makeItem(quantity: 3);

        $this->expectException(\RuntimeException::class);

        $this->service->reserve($item, 10);
    }

    /** @test */
    public function it_returns_low_stock_items(): void
    {
        $low    = $this->makeItem(quantity: 2,  threshold: 5);
        $ok     = $this->makeItem(quantity: 10, threshold: 5);
        $noSla  = $this->makeItem(quantity: 0,  threshold: 0); // no threshold = no alert

        $result = $this->service->getLowStock(tenantId: 1);

        $this->assertTrue($result->contains('id', $low->id));
        $this->assertFalse($result->contains('id', $ok->id));
        $this->assertFalse($result->contains('id', $noSla->id));
    }
}
