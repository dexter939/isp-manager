<?php

declare(strict_types=1);

namespace Modules\Billing\Bundles\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Bundles\Enums\BillingPeriod;
use Modules\Billing\Bundles\Models\BundlePlan;

class BundlePlanFactory extends Factory
{
    protected $model = BundlePlan::class;

    public function definition(): array
    {
        return [
            'tenant_id'      => 1,
            'name'           => $this->faker->words(3, true) . ' Bundle',
            'description'    => $this->faker->sentence(),
            'price_amount'   => $this->faker->numberBetween(1990, 5990),
            'price_currency' => 'EUR',
            'billing_period' => BillingPeriod::Monthly->value,
            'is_active'      => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function annual(): static
    {
        return $this->state(['billing_period' => BillingPeriod::Annual->value]);
    }
}
