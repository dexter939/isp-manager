<?php

declare(strict_types=1);

namespace Modules\Contracts\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contracts\Enums\CustomerStatus;
use Modules\Contracts\Enums\CustomerType;
use Modules\Contracts\Enums\PaymentMethod;
use Modules\Contracts\Models\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([CustomerType::Privato->value, CustomerType::Azienda->value]);

        return [
            'tenant_id'           => Tenant::factory(),
            'type'                => $type,
            'ragione_sociale'     => $type === CustomerType::Azienda->value ? $this->faker->company() : null,
            'nome'                => $type === CustomerType::Privato->value ? $this->faker->firstName() : null,
            'cognome'             => $type === CustomerType::Privato->value ? $this->faker->lastName() : null,
            'codice_fiscale'      => strtoupper($this->faker->bothify('???###??###??###')),
            'piva'                => $type === CustomerType::Azienda->value ? $this->faker->numerify('###########') : null,
            'email'               => $this->faker->unique()->safeEmail(),
            'pec'                 => null,
            'telefono'            => $this->faker->phoneNumber(),
            'cellulare'           => $this->faker->phoneNumber(),
            'indirizzo_fatturazione' => [
                'via'      => $this->faker->streetName(),
                'civico'   => $this->faker->buildingNumber(),
                'comune'   => $this->faker->city(),
                'cap'      => $this->faker->postcode(),
                'provincia'=> $this->faker->stateAbbr(),
            ],
            'payment_method'      => PaymentMethod::Bonifico->value,
            'iban'                => null,
            'stripe_customer_id'  => null,
            'sepa_mandate_id'     => null,
            'status'              => CustomerStatus::Active->value,
            'notes'               => null,
            'marketing_consent'   => false,
        ];
    }

    public function privato(): static
    {
        return $this->state([
            'type'            => CustomerType::Privato->value,
            'ragione_sociale' => null,
            'nome'            => $this->faker->firstName(),
            'cognome'         => $this->faker->lastName(),
        ]);
    }

    public function azienda(): static
    {
        return $this->state([
            'type'            => CustomerType::Azienda->value,
            'ragione_sociale' => $this->faker->company(),
            'nome'            => null,
            'cognome'         => null,
        ]);
    }

    public function withMarketingConsent(): static
    {
        return $this->state(['marketing_consent' => true]);
    }

    public function sepa(): static
    {
        return $this->state([
            'payment_method' => PaymentMethod::Sdd->value,
            'iban'           => 'IT60X0542811101000000123456',
        ]);
    }
}
