<?php

declare(strict_types=1);

namespace Modules\Network\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Network\Enums\FloatingIpStatus;
use Modules\Network\Jobs\FloatingIpMonitorJob;
use Modules\Network\Models\FloatingIpPair;
use Modules\Network\Services\FloatingIpService;
use Tests\TestCase;

class FloatingIpMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.carrier_mock' => false]);
    }

    /** @test */
    public function test_monitor_triggers_failover_when_master_offline(): void
    {
        $tenantId   = (string) \Illuminate\Support\Str::uuid();
        $masterId   = (string) \Illuminate\Support\Str::uuid();
        $failoverId = (string) \Illuminate\Support\Str::uuid();

        $pair = FloatingIpPair::create([
            'tenant_id'                 => $tenantId,
            'name'                      => 'Monitor Test Pair',
            'master_pppoe_account_id'   => $masterId,
            'failover_pppoe_account_id' => $failoverId,
            'status'                    => FloatingIpStatus::MasterActive,
        ]);

        // Mock DB::selectOne: no active session, and last stop was 10 minutes ago
        DB::shouldReceive('selectOne')
            ->with(
                'SELECT * FROM radacct WHERE username = ? AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1',
                Mockery::type('array')
            )
            ->andReturn(null); // master offline

        DB::shouldReceive('selectOne')
            ->with(
                'SELECT acctstoptime FROM radacct WHERE username = ? AND acctstoptime IS NOT NULL ORDER BY acctstoptime DESC LIMIT 1',
                Mockery::type('array')
            )
            ->andReturn((object) ['acctstoptime' => now()->subMinutes(10)->toDateTimeString()]);

        // Assert FloatingIpService::triggerFailover is called
        $serviceMock = Mockery::mock(FloatingIpService::class);
        $serviceMock->shouldReceive('triggerFailover')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $pair->id), 'radius_disconnect');

        $this->app->instance(FloatingIpService::class, $serviceMock);

        config(['floating_ip.offline_threshold_minutes' => 5]);

        $job = new FloatingIpMonitorJob();
        $job->handle($serviceMock);
    }

    /** @test */
    public function test_monitor_skips_in_carrier_mock_mode(): void
    {
        config(['app.carrier_mock' => true]);

        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::pattern('/MOCK.*Skipping/'), Mockery::any());

        $serviceMock = Mockery::mock(FloatingIpService::class);
        $serviceMock->shouldNotReceive('triggerFailover');
        $serviceMock->shouldNotReceive('triggerRecovery');

        $job = new FloatingIpMonitorJob();
        $job->handle($serviceMock);
    }
}
