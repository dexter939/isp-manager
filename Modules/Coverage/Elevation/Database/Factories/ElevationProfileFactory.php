<?php

declare(strict_types=1);

namespace Modules\Coverage\Elevation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Coverage\Elevation\Models\ElevationProfile;

class ElevationProfileFactory extends Factory
{
    protected $model = ElevationProfile::class;

    public function definition(): array
    {
        $siteLat  = 45.4654;
        $siteLon  = 9.1866;
        $custLat  = $siteLat + $this->faker->randomFloat(4, 0.001, 0.05);
        $custLon  = $siteLon + $this->faker->randomFloat(4, 0.001, 0.05);

        return [
            'tenant_id'                => 1,
            'network_site_id'          => Str::uuid()->toString(),
            'site_lat'                 => $siteLat,
            'site_lon'                 => $siteLon,
            'customer_lat'             => $custLat,
            'customer_lon'             => $custLon,
            'customer_address'         => null,
            'antenna_height_m'         => 10,
            'cpe_height_m'             => 3,
            'frequency_ghz'            => 5.8,
            'distance_km'              => $this->faker->randomFloat(2, 0.5, 15.0),
            'has_obstruction'          => false,
            'max_obstruction_m'        => 0.0,
            'fresnel_clearance_percent'=> $this->faker->numberBetween(60, 100),
            'profile_data'             => json_encode([]),
            'calculated_at'            => now(),
        ];
    }

    public function obstructed(): static
    {
        return $this->state([
            'has_obstruction'          => true,
            'max_obstruction_m'        => $this->faker->randomFloat(1, 1, 20),
            'fresnel_clearance_percent'=> $this->faker->numberBetween(0, 59),
        ]);
    }

    public function noFrequency(): static
    {
        return $this->state([
            'frequency_ghz'            => null,
            'fresnel_clearance_percent'=> null,
        ]);
    }
}
