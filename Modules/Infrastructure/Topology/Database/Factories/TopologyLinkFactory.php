<?php

declare(strict_types=1);

namespace Modules\Infrastructure\Topology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Infrastructure\Topology\Models\TopologyLink;

class TopologyLinkFactory extends Factory
{
    protected $model = TopologyLink::class;

    public function definition(): array
    {
        return [
            'tenant_id'        => 1,
            'source_device_id' => Str::uuid()->toString(),
            'target_device_id' => Str::uuid()->toString(),
            'link_type'        => $this->faker->randomElement(['fiber', 'ethernet', 'wireless', 'sfp']),
            'bandwidth_mbps'   => $this->faker->randomElement([100, 1000, 10000]),
            'status'           => 'active',
            'notes'            => null,
        ];
    }

    public function wireless(): static
    {
        return $this->state(['link_type' => 'wireless', 'bandwidth_mbps' => 300]);
    }

    public function fiber(): static
    {
        return $this->state(['link_type' => 'fiber', 'bandwidth_mbps' => 10000]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
