<?php

declare(strict_types=1);

namespace Modules\Network\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Network\Models\BtsStation;

class BtsStationFactory extends Factory
{
    protected $model = BtsStation::class;

    public function definition(): array
    {
        // Coordinate centrate sull'Italia
        $lat = $this->faker->randomFloat(6, 37.5, 47.0);
        $lng = $this->faker->randomFloat(6, 7.0, 18.5);

        return [
            'tenant_id'     => Tenant::factory(),
            'name'          => 'BTS ' . $this->faker->city(),
            'code'          => strtoupper($this->faker->unique()->bothify('BTS-??-####')),
            'type'          => $this->faker->randomElement(['fwa', 'lte', 'wimax']),
            'lat'           => $lat,
            'lng'           => $lng,
            'address'       => $this->faker->streetAddress(),
            'ip_management' => $this->faker->localIpv4(),
            'status'        => 'active',
            'max_clients'   => $this->faker->numberBetween(50, 500),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function withoutIp(): static
    {
        return $this->state(['ip_management' => null]);
    }
}
