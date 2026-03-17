<?php

declare(strict_types=1);

namespace Modules\Contracts\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contracts\Enums\BillingCycle;
use Modules\Contracts\Enums\CarrierEnum;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Models\ServicePlan;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id'       => $tenant->id,
            'customer_id'     => Customer::factory()->for($tenant, 'tenant'),
            'service_plan_id' => ServicePlan::factory()->for($tenant, 'tenant'),
            'carrier'         => CarrierEnum::OpenFiber->value,
            'indirizzo_installazione' => [
                'via'       => $this->faker->streetName(),
                'civico'    => $this->faker->buildingNumber(),
                'comune'    => $this->faker->city(),
                'cap'       => $this->faker->postcode(),
                'provincia' => $this->faker->stateAbbr(),
            ],
            'codice_ui'       => 'IT-RM-' . $this->faker->unique()->numerify('####-#####'),
            'id_building'     => null,
            'billing_cycle'   => BillingCycle::Monthly->value,
            'billing_day'     => $this->faker->numberBetween(1, 28),
            'monthly_price'   => $this->faker->randomFloat(2, 19.90, 59.90),
            'activation_fee'  => 0.00,
            'modem_fee'       => 0.00,
            'activation_date' => now()->subMonths($this->faker->numberBetween(1, 24))->toDateString(),
            'termination_date'=> null,
            'min_end_date'    => now()->addMonths(12)->toDateString(),
            'status'          => ContractStatus::Active->value,
            'signed_at'       => now()->subMonths(2),
            'agent_id'        => null,
            'notes'           => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => ContractStatus::Active->value]);
    }

    public function terminated(): static
    {
        return $this->state([
            'status'           => ContractStatus::Terminated->value,
            'termination_date' => now()->subDays(30)->toDateString(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => ContractStatus::Suspended->value]);
    }

    public function fibercop(): static
    {
        return $this->state(['carrier' => CarrierEnum::FiberCop->value]);
    }

    public function unsigned(): static
    {
        return $this->state(['signed_at' => null, 'status' => ContractStatus::Draft->value]);
    }
}
