<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Contracts\Models\Contract;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Jobs\SendActivationOrderJob;
use Modules\Provisioning\Models\CarrierOrder;
use Modules\Provisioning\Models\VlanPool;
use Modules\Provisioning\Services\CarrierGateway;
use Modules\Provisioning\Services\OrderStateMachine;
use Modules\Provisioning\Services\VlanManager;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function full_activation_flow_with_mock_carrier(): void
    {
        config(['app.carrier_mock' => true]);

        // Setup
        $agent    = $this->makeAgent();
        $contract = Contract::factory()->active()->create(['tenant_id' => $agent->tenant_id]);
        VlanPool::create(['tenant_id' => $agent->tenant_id, 'carrier' => 'openfiber', 'vlan_type' => 'C-VLAN', 'vlan_id' => 200, 'status' => 'free']);

        // 1. Crea ordine via API
        $response = $this->actingAs($agent, 'sanctum')
            ->postJson('/api/v1/orders', [
                'contract_id' => $contract->id,
                'order_type'  => 'activation',
            ])
            ->assertCreated();

        $orderId = $response->json('id');
        $this->assertEquals(OrderState::Draft->value, $response->json('state'));

        // 2. Invia ordine
        Queue::fake();
        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/v1/orders/{$orderId}/send")
            ->assertOk();

        Queue::assertPushed(SendActivationOrderJob::class);

        // 3. Esegui job direttamente (mock carrier)
        Queue::restore();
        $order = CarrierOrder::findOrFail($orderId);
        (new SendActivationOrderJob($orderId))->handle(
            gateway: app(CarrierGateway::class),
            stateMachine: app(OrderStateMachine::class),
            vlanManager: app(VlanManager::class),
        );

        $order->refresh();
        $this->assertEquals(OrderState::Sent, $order->state);
        $this->assertNotNull($order->cvlan);
        $this->assertNotNull($order->codice_ordine_of);
    }

    /** @test */
    public function agent_cannot_see_other_tenant_orders(): void
    {
        $agent       = $this->makeAgent();
        $otherOrder  = CarrierOrder::factory()->create(['tenant_id' => 999]);

        $this->actingAs($agent, 'sanctum')
            ->getJson("/api/v1/orders/{$otherOrder->id}")
            ->assertForbidden();
    }

    /** @test */
    public function cancel_from_draft_state_works(): void
    {
        $agent = $this->makeAgent();
        $order = CarrierOrder::factory()->create([
            'tenant_id' => $agent->tenant_id,
            'state'     => OrderState::Draft->value,
        ]);

        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'Richiesta cliente'])
            ->assertOk();

        $this->assertEquals(OrderState::Cancelled, $order->fresh()->state);
    }

    /** @test */
    public function cancel_from_completed_state_fails(): void
    {
        $agent = $this->makeAgent();
        $order = CarrierOrder::factory()->create([
            'tenant_id' => $agent->tenant_id,
            'state'     => OrderState::Completed->value,
        ]);

        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertUnprocessable();
    }

    private function makeAgent(): \App\Models\User
    {
        return \App\Models\User::factory()->create(['tenant_id' => 1])->assignRole('agent');
    }
}
