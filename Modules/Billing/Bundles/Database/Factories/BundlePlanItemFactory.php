<?php

declare(strict_types=1);

namespace Modules\Billing\Bundles\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Bundles\Models\BundlePlanItem;

class BundlePlanItemFactory extends Factory
{
    protected $model = BundlePlanItem::class;

    public function definition(): array
    {
        return [
            'bundle_plan_id'   => BundlePlanFactory::new(),
            'service_type'     => $this->faker->randomElement(['internet', 'voip', 'iptv', 'support']),
            'description'      => $this->faker->sentence(4),
            'list_price_amount'=> $this->faker->numberBetween(500, 3000),
            'sort_order'       => $this->faker->numberBetween(1, 10),
        ];
    }
}
