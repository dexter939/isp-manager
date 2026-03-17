<?php

declare(strict_types=1);

namespace Modules\Contracts\Tests\Feature;

use Modules\Contracts\Enums\ContractStatus;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Models\ServicePlan;
use Tests\TestCase;

class ContractApiTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function agent_can_list_contracts(): void
    {
        $agent = $this->makeAgent();
        Contract::factory()->count(3)->create(['tenant_id' => $agent->tenant_id]);

        $this->actingAs($agent, 'sanctum')
            ->getJson('/api/v1/contracts')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function agent_can_create_a_draft_contract(): void
    {
        $agent    = $this->makeAgent();
        $customer = Customer::factory()->create(['tenant_id' => $agent->tenant_id]);
        $plan     = ServicePlan::factory()->active()->create(['tenant_id' => $agent->tenant_id]);

        $this->actingAs($agent, 'sanctum')
            ->postJson('/api/v1/contracts', [
                'customer_id'     => $customer->id,
                'service_plan_id' => $plan->id,
                'indirizzo_installazione' => [
                    'via'      => 'Via Roma',
                    'civico'   => '1',
                    'comune'   => 'Milano',
                    'provincia'=> 'MI',
                    'cap'      => '20100',
                ],
                'billing_cycle' => 'monthly',
                'billing_day'   => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('status', ContractStatus::Draft->value);
    }

    /** @test */
    public function contract_prices_are_snapshotted_from_plan(): void
    {
        $agent    = $this->makeAgent();
        $customer = Customer::factory()->create(['tenant_id' => $agent->tenant_id]);
        $plan     = ServicePlan::factory()->active()->create([
            'tenant_id'     => $agent->tenant_id,
            'price_monthly' => '29.90',
            'activation_fee'=> '50.00',
        ]);

        $response = $this->actingAs($agent, 'sanctum')
            ->postJson('/api/v1/contracts', [
                'customer_id'     => $customer->id,
                'service_plan_id' => $plan->id,
                'indirizzo_installazione' => [
                    'via' => 'Via Test', 'civico' => '1',
                    'comune' => 'Roma', 'provincia' => 'RM', 'cap' => '00100',
                ],
                'billing_cycle' => 'monthly',
                'billing_day'   => 15,
            ])
            ->assertCreated();

        $this->assertEquals('29.90', $response->json('monthly_price'));
        $this->assertEquals('50.00', $response->json('activation_fee'));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_contracts(): void
    {
        $this->getJson('/api/v1/contracts')->assertUnauthorized();
    }

    /** @test */
    public function agent_cannot_see_other_tenant_contracts(): void
    {
        $agent         = $this->makeAgent();
        $otherContract = Contract::factory()->create(['tenant_id' => 999]);

        $this->actingAs($agent, 'sanctum')
            ->getJson("/api/v1/contracts/{$otherContract->id}")
            ->assertForbidden();
    }

    /** @test */
    public function contract_termination_requires_valid_transition(): void
    {
        $agent    = $this->makeAgent();
        $contract = Contract::factory()->create([
            'tenant_id' => $agent->tenant_id,
            'status'    => ContractStatus::Draft->value,
        ]);

        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/v1/contracts/{$contract->id}/terminate")
            ->assertUnprocessable(); // Draft → Terminated non è valido
    }

    // ---- Helpers ----

    private function makeAgent(): \App\Models\User
    {
        return \App\Models\User::factory()->create(['tenant_id' => 1])->assignRole('agent');
    }
}
