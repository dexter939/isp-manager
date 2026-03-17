<?php

namespace Modules\Maintenance\InventoryRma\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryRmaFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_inventory_models(): void
    {
        $response = $this->getJson('/api/inventory/models');
        $response->assertStatus(200);
    }

    public function test_stock_levels_report(): void
    {
        $response = $this->getJson('/api/inventory/reports/stock-levels');
        $response->assertStatus(200);
    }
}
