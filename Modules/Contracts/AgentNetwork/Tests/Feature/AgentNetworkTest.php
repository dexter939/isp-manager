<?php

namespace Modules\Contracts\AgentNetwork\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contracts\AgentNetwork\Models\Agent;
use Modules\Contracts\AgentNetwork\Models\CommissionEntry;
use Modules\Contracts\AgentNetwork\Services\CommissionLiquidationService;
use Tests\TestCase;

class AgentNetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_agent_with_user(): void
    {
        $user  = $this->createUser();
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson('/api/admin/agents', [
            'user_id'        => $user->id,
            'business_name'  => 'Test Agent SRL',
            'codice_fiscale' => 'RSSMRA80A01H501Z',
            'iban'           => 'IT60X0542811101000000123456',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('agents', ['user_id' => $user->id]);
    }

    public function test_generates_monthly_liquidation(): void
    {
        $agent   = Agent::factory()->create();
        $entries = CommissionEntry::factory()->count(3)->create([
            'agent_id'     => $agent->id,
            'status'       => 'pending',
            'amount_cents' => 1000,
            'period_month' => now()->startOfMonth()->toDateString(),
        ]);

        $service = app(CommissionLiquidationService::class);
        $result  = $service->generateLiquidation(now());

        $this->assertEquals(1, $result['liquidations_created']);
        $this->assertDatabaseHas('commission_liquidations', [
            'agent_id'           => $agent->id,
            'total_amount_cents' => 3000,
        ]);
    }

    public function test_approves_liquidation(): void
    {
        $liquidation = \Modules\Contracts\AgentNetwork\Models\CommissionLiquidation::factory()->create([
            'status' => 'draft',
        ]);
        $admin  = $this->createAdminUser();
        $service = app(CommissionLiquidationService::class);

        $service->approveLiquidation($liquidation, $admin);
        $this->assertEquals('approved', $liquidation->fresh()->status);
    }
}
