<?php

declare(strict_types=1);

namespace Modules\Billing\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Enums\PrepaidWalletStatus;
use Modules\Billing\Models\PrepaidWallet;
use Modules\Contracts\Models\Customer;

class PrepaidWalletFactory extends Factory
{
    protected $model = PrepaidWallet::class;

    public function definition(): array
    {
        return [
            'tenant_id'                    => Tenant::factory(),
            'customer_id'                  => Customer::factory(),
            'balance_amount'               => $this->faker->numberBetween(0, 10000),
            'balance_currency'             => 'EUR',
            'status'                       => PrepaidWalletStatus::Active,
            'low_balance_threshold_amount' => 500,
            'auto_suspend_on_zero'         => true,
        ];
    }

    /**
     * State: wallet is exhausted (balance = 0).
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_amount' => 0,
            'status'         => PrepaidWalletStatus::Exhausted,
        ]);
    }

    /**
     * State: wallet is below the low balance threshold.
     */
    public function lowBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_amount'               => $this->faker->numberBetween(1, 499),
            'low_balance_threshold_amount' => 500,
            'status'                       => PrepaidWalletStatus::Active,
        ]);
    }

    /**
     * State: wallet is funded with a specific amount (default 5000 cents = €50).
     */
    public function funded(int $cents = 5000): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_amount' => $cents,
            'status'         => PrepaidWalletStatus::Active,
        ]);
    }
}
