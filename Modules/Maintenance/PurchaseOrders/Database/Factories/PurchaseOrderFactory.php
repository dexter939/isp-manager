<?php

declare(strict_types=1);

namespace Modules\Maintenance\PurchaseOrders\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Maintenance\PurchaseOrders\Models\PurchaseOrder;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id'         => 1,
            'supplier_id'       => Str::uuid()->toString(),
            'status'            => 'draft',
            'total_amount'      => $this->faker->numberBetween(5000, 50000),
            'expected_delivery' => now()->addDays($this->faker->numberBetween(5, 30))->toDateString(),
            'notes'             => null,
            'created_by'        => 'manual',
            'reorder_rule_id'   => null,
            'approved_by'       => null,
            'approved_at'       => null,
            'sent_at'           => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => 'approved',
            'approved_by' => Str::uuid()->toString(),
            'approved_at' => now(),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status'  => 'approved',
            'sent_at' => now(),
        ])->approved();
    }

    public function autoReorder(): static
    {
        return $this->state(['created_by' => 'auto_reorder']);
    }
}
