<?php

declare(strict_types=1);

namespace Modules\Billing\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Enums\PaymentStatus;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\Payment;
use Modules\Contracts\Models\Customer;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'tenant_id'                => Tenant::factory(),
            'invoice_id'               => Invoice::factory(),
            'customer_id'              => Customer::factory(),
            'method'                   => 'stripe',
            'amount'                   => $this->faker->randomFloat(2, 10.00, 100.00),
            'currency'                 => 'EUR',
            'status'                   => PaymentStatus::Pending->value,
            'stripe_payment_intent_id' => null,
            'stripe_charge_id'         => null,
            'stripe_error'             => null,
            'sepa_mandate_id'          => null,
            'sepa_end_to_end_id'       => null,
            'sepa_return_code'         => null,
            'sepa_file_id'             => null,
            'processed_at'             => null,
            'notes'                    => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status'       => PaymentStatus::Completed->value,
            'processed_at' => now()->subHours(2),
            'stripe_payment_intent_id' => 'pi_' . $this->faker->unique()->bothify('????????????????????'),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'       => PaymentStatus::Failed->value,
            'stripe_error' => 'Your card was declined.',
        ]);
    }

    public function stripe(): static
    {
        return $this->state([
            'method'                   => 'stripe',
            'stripe_payment_intent_id' => 'pi_' . $this->faker->bothify('????????????????????'),
        ]);
    }

    public function sepa(): static
    {
        return $this->state([
            'method'             => 'sdd',
            'sepa_end_to_end_id' => 'INV-' . $this->faker->numerify('######'),
        ]);
    }
}
