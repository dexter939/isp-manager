<?php

declare(strict_types=1);

namespace Modules\Maintenance\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contracts\Database\Factories\ContractFactory;
use Modules\Contracts\Database\Factories\CustomerFactory;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;
use Modules\Maintenance\Models\TroubleTicket;

class TroubleTicketFactory extends Factory
{
    protected $model = TroubleTicket::class;

    public function definition(): array
    {
        static $sequence = 1;

        $priority = $this->faker->randomElement(TicketPriority::cases());
        $openedAt = now()->subHours($this->faker->numberBetween(1, 72));

        return [
            'tenant_id'       => Tenant::factory(),
            'customer_id'     => Customer::factory(),
            'contract_id'     => null,
            'assigned_to'     => null,
            'ticket_number'   => 'TK-' . now()->year . '-' . str_pad($sequence++, 6, '0', STR_PAD_LEFT),
            'title'           => $this->faker->sentence(6),
            'description'     => $this->faker->paragraph(),
            'status'          => TicketStatus::Open->value,
            'priority'        => $priority->value,
            'type'            => $this->faker->randomElement(['assurance', 'billing', 'provisioning', 'other']),
            'source'          => $this->faker->randomElement(['manual', 'ai', 'whatsapp', 'api']),
            'carrier'         => null,
            'carrier_ticket_id' => null,
            'opened_at'       => $openedAt,
            'first_response_at' => null,
            'resolved_at'     => null,
            'closed_at'       => null,
            'due_at'          => $openedAt->copy()->addHours($priority->resolutionHours()),
            'resolution_notes'=> null,
        ];
    }

    public function critical(): static
    {
        return $this->state([
            'priority' => TicketPriority::Critical->value,
            'due_at'   => now()->addHours(TicketPriority::Critical->resolutionHours()),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status'            => TicketStatus::InProgress->value,
            'first_response_at' => now()->subMinutes(30),
        ]);
    }

    public function resolved(): static
    {
        return $this->state([
            'status'           => TicketStatus::Resolved->value,
            'resolved_at'      => now()->subHours(1),
            'resolution_notes' => 'Problema risolto dal tecnico.',
        ]);
    }

    public function assurance(): static
    {
        return $this->state(['type' => 'assurance']);
    }

    public function withContract(): static
    {
        return $this->state(['contract_id' => Contract::factory()]);
    }
}
