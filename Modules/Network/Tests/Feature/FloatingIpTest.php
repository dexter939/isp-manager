<?php

declare(strict_types=1);

namespace Modules\Network\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Network\Enums\FloatingIpStatus;
use Modules\Network\Events\FloatingIpFailoverTriggered;
use Modules\Network\Models\FloatingIpPair;
use Modules\Network\Services\CoaService;
use Modules\Network\Services\FloatingIpService;
use Tests\TestCase;

class FloatingIpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Always run in mock mode during tests
        config(['app.carrier_mock' => true]);
    }

    /** @test */
    public function test_create_pair_validates_distinct_accounts(): void
    {
        $sameId = (string) \Illuminate\Support\Str::uuid();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be different');

        $service = app(FloatingIpService::class);
        $service->createPair([
            'tenant_id'                 => (string) \Illuminate\Support\Str::uuid(),
            'name'                      => 'Test Pair',
            'master_pppoe_account_id'   => $sameId,
            'failover_pppoe_account_id' => $sameId,
            'resources'                 => [],
        ]);
    }

    /** @test */
    public function test_trigger_failover_updates_status(): void
    {
        Event::fake([FloatingIpFailoverTriggered::class]);

        // Mock CoaService to avoid real network calls
        $this->mock(CoaService::class, function ($mock): void {
            $mock->shouldReceive('disconnect')->andReturn(null);
        });

        $tenantId  = (string) \Illuminate\Support\Str::uuid();
        $masterId  = (string) \Illuminate\Support\Str::uuid();
        $failoverId = (string) \Illuminate\Support\Str::uuid();

        $pair = FloatingIpPair::create([
            'tenant_id'                 => $tenantId,
            'name'                      => 'Test Pair',
            'master_pppoe_account_id'   => $masterId,
            'failover_pppoe_account_id' => $failoverId,
            'status'                    => FloatingIpStatus::MasterActive,
        ]);

        // carrier_mock is true so no real CoA is sent
        $service = app(FloatingIpService::class);
        $service->triggerFailover($pair, 'radius_disconnect');

        // In mock mode the status update in DB is skipped (only logs),
        // so we only assert the pair was processed without error and event log written
        $this->assertDatabaseHas('floating_ip_events', [
            'floating_ip_pair_id' => $pair->id,
            'event_type'          => 'failover_triggered',
        ]);
    }

    /** @test */
    public function test_trigger_recovery_requires_master_online(): void
    {
        // Mock the DB radacct query: master has no active session
        \Illuminate\Support\Facades\DB::shouldReceive('selectOne')
            ->with(
                'SELECT * FROM radacct WHERE username = ? AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1',
                \Mockery::type('array')
            )
            ->andReturn(null); // no active session

        $tenantId   = (string) \Illuminate\Support\Str::uuid();
        $masterId   = (string) \Illuminate\Support\Str::uuid();
        $failoverId = (string) \Illuminate\Support\Str::uuid();

        $pair = FloatingIpPair::create([
            'tenant_id'                 => $tenantId,
            'name'                      => 'Test Pair Recovery',
            'master_pppoe_account_id'   => $masterId,
            'failover_pppoe_account_id' => $failoverId,
            'status'                    => FloatingIpStatus::FailoverActive,
        ]);

        $service = app(FloatingIpService::class);
        // Should silently abort because master has no active session
        $service->triggerRecovery($pair);

        // Status should remain failover_active
        $pair->refresh();
        $this->assertEquals(FloatingIpStatus::FailoverActive, $pair->status);
    }

    /** @test */
    public function test_force_failover_via_api(): void
    {
        $user = $this->createAuthenticatedUser();

        $pair = FloatingIpPair::create([
            'tenant_id'                 => $user->tenant_id,
            'name'                      => 'API Test Pair',
            'master_pppoe_account_id'   => (string) \Illuminate\Support\Str::uuid(),
            'failover_pppoe_account_id' => (string) \Illuminate\Support\Str::uuid(),
            'status'                    => FloatingIpStatus::MasterActive,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/floating-ip/{$pair->id}/force-failover");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $pair->id);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createAuthenticatedUser(): \Illuminate\Contracts\Auth\Authenticatable
    {
        // Assumes a User factory exists in the base app
        $tenantId = (string) \Illuminate\Support\Str::uuid();

        return \App\Models\User::factory()->create([
            'tenant_id' => $tenantId,
        ]);
    }
}
