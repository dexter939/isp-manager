<?php

declare(strict_types=1);

namespace Modules\Contracts\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contracts\Enums\CarrierEnum;
use Modules\Contracts\Models\ServicePlan;

class ServicePlanFactory extends Factory
{
    protected $model = ServicePlan::class;

    public function definition(): array
    {
        $tech = $this->faker->randomElement(['FTTH', 'FTTC', 'FWA']);
        $carrier = $this->faker->randomElement([CarrierEnum::OpenFiber->value, CarrierEnum::FiberCop->value]);

        return [
            'tenant_id'            => Tenant::factory(),
            'name'                 => "Piano {$tech} " . $this->faker->numerify('##') . ' Mbps',
            'carrier'              => $carrier,
            'technology'           => $tech,
            'price_monthly'        => $this->faker->randomFloat(2, 19.90, 59.90),
            'activation_fee'       => $this->faker->randomFloat(2, 0, 99.00),
            'modem_fee'            => 0.00,
            'carrier_product_code' => strtoupper($this->faker->bothify('??-####-??')),
            'bandwidth_dl'         => $this->faker->randomElement([100, 200, 500, 1000, 2500]),
            'bandwidth_ul'         => $this->faker->randomElement([30, 50, 100, 300]),
            'sla_type'             => 'best_effort',
            'mtr_hours'            => null,
            'is_active'            => true,
            'is_public'            => true,
            'min_contract_months'  => 24,
            'description'          => null,
        ];
    }

    public function ftth(): static
    {
        return $this->state([
            'technology'    => 'FTTH',
            'carrier'       => CarrierEnum::OpenFiber->value,
            'bandwidth_dl'  => 1000,
            'bandwidth_ul'  => 300,
        ]);
    }

    public function fttc(): static
    {
        return $this->state([
            'technology'    => 'FTTC',
            'carrier'       => CarrierEnum::FiberCop->value,
            'bandwidth_dl'  => 200,
            'bandwidth_ul'  => 20,
        ]);
    }

    public function fwa(): static
    {
        return $this->state([
            'technology'    => 'FWA',
            'carrier'       => CarrierEnum::Generic->value,
            'bandwidth_dl'  => 100,
            'bandwidth_ul'  => 30,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
