<?php

declare(strict_types=1);

namespace Modules\Provisioning\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contracts\Database\Factories\ContractFactory;
use Modules\Contracts\Models\Contract;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Enums\OrderType;
use Modules\Provisioning\Models\CarrierOrder;

class CarrierOrderFactory extends Factory
{
    protected $model = CarrierOrder::class;

    public function definition(): array
    {
        static $sequence = 1;

        return [
            'tenant_id'          => Tenant::factory(),
            'contract_id'        => Contract::factory(),
            'carrier'            => 'openfiber',
            'order_type'         => OrderType::Activation->value,
            'codice_ordine_olo'  => 'ISP-' . now()->year . '-' . str_pad($sequence++, 6, '0', STR_PAD_LEFT),
            'codice_ordine_of'   => null,
            'state'              => OrderState::Draft->value,
            'scheduled_date'     => null,
            'cvlan'              => null,
            'gpon_attestazione'  => null,
            'id_apparato_consegnato' => null,
            'vlan_pool_id'       => null,
            'payload_sent'       => null,
            'payload_received'   => null,
            'last_error'         => null,
            'retry_count'        => 0,
            'next_retry_at'      => null,
            'sent_by'            => null,
            'sent_at'            => null,
            'completed_at'       => null,
            'notes'              => null,
        ];
    }

    public function sent(): static
    {
        return $this->state([
            'state'   => OrderState::Sent->value,
            'sent_at' => now()->subHours(2),
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'state'            => OrderState::Accepted->value,
            'codice_ordine_of' => 'OF-' . $this->faker->numerify('##########'),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'state'        => OrderState::Completed->value,
            'completed_at' => now()->subDays(1),
            'id_apparato_consegnato' => 'ONT-' . strtoupper($this->faker->bothify('????####????')),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'state'      => OrderState::Ko->value,
            'last_error' => 'Connection timeout',
            'retry_count'=> 1,
        ]);
    }

    public function fibercop(): static
    {
        return $this->state(['carrier' => 'fibercop']);
    }
}
