<?php

declare(strict_types=1);

namespace Modules\Network\Tests\Unit;

use Illuminate\Support\Facades\Log;
use Modules\Network\Models\RadiusProfile;
use Modules\Network\Models\RadiusUser;
use Modules\Network\Services\CoaService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoaServiceTest extends TestCase
{
    use RefreshDatabase;

    private CoaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Forza CARRIER_MOCK per i test
        config(['app.carrier_mock' => true]);
        $this->service = app(CoaService::class);
    }

    /** @test */
    public function it_suspends_active_user_to_walled_garden(): void
    {
        $user = RadiusUser::factory()->create([
            'status'         => 'active',
            'walled_garden'  => false,
            'nas_ip'         => '10.0.0.1',
            'acct_session_id' => 'sess-001',
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $this->service->suspendToWalledGarden($user);

        $user->refresh();
        $this->assertEquals('suspended', $user->status);
        $this->assertTrue($user->walled_garden);
        $this->assertNotNull($user->walled_garden_token);
    }

    /** @test */
    public function it_restores_access_for_suspended_user(): void
    {
        $user = RadiusUser::factory()->create([
            'status'              => 'suspended',
            'walled_garden'       => true,
            'walled_garden_token' => 'abc123',
            'nas_ip'              => '10.0.0.1',
            'acct_session_id'     => 'sess-001',
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $this->service->restoreAccess($user);

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertFalse($user->walled_garden);
        $this->assertNull($user->walled_garden_token);
    }

    /** @test */
    public function it_skips_restore_if_user_not_suspended(): void
    {
        $user = RadiusUser::factory()->create([
            'status'        => 'active',
            'walled_garden' => false,
        ]);

        // Non deve fare nulla — nessuna eccezione
        $this->service->restoreAccess($user);

        $this->assertEquals('active', $user->fresh()->status);
    }

    /** @test */
    public function it_suspends_user_without_active_session(): void
    {
        $user = RadiusUser::factory()->create([
            'status'          => 'active',
            'nas_ip'          => null,
            'acct_session_id' => null,
        ]);

        Log::shouldReceive('warning')->once();

        $this->service->suspendToWalledGarden($user);

        $this->assertEquals('suspended', $user->fresh()->status);
    }

    /** @test */
    public function radius_profile_generates_correct_mikrotik_rate_limit(): void
    {
        $profile = RadiusProfile::factory()->create([
            'rate_dl_kbps' => 1_000_000, // 1Gbps
            'rate_ul_kbps' => 1_000_000,
        ]);

        $this->assertEquals('1G/1G', $profile->mikrotikRateLimit());
    }

    /** @test */
    public function radius_profile_generates_correct_walled_garden_limit(): void
    {
        $profile = RadiusProfile::factory()->create([
            'walled_dl_kbps' => 128,
            'walled_ul_kbps' => 128,
        ]);

        $this->assertEquals('128k/128k', $profile->mikrotikWalledGardenLimit());
    }
}
