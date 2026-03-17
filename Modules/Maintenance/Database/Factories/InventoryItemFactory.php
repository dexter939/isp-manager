<?php

declare(strict_types=1);

namespace Modules\Maintenance\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Maintenance\Models\InventoryItem;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['ont', 'router', 'cpe_fwa', 'sim', 'cable', 'accessory']);

        return [
            'tenant_id'          => Tenant::factory(),
            'name'               => $this->faker->words(3, true),
            'sku'                => strtoupper($this->faker->unique()->bothify('SKU-???-####')),
            'type'               => $type,
            'brand'              => $this->faker->randomElement(['TP-Link', 'Huawei', 'Zyxel', 'MikroTik']),
            'model'              => strtoupper($this->faker->bothify('??-###')),
            'quantity'           => $this->faker->numberBetween(5, 100),
            'quantity_reserved'  => 0,
            'reorder_threshold'  => $this->faker->numberBetween(2, 10),
            'unit_cost'          => $this->faker->randomFloat(2, 20.00, 300.00),
            'location'           => 'Magazzino principale',
            'notes'              => null,
        ];
    }

    public function lowStock(): static
    {
        return $this->state([
            'quantity'          => 1,
            'reorder_threshold' => 5,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state([
            'quantity'          => 0,
            'reorder_threshold' => 5,
        ]);
    }
}
