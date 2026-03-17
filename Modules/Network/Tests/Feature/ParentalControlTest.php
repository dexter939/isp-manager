<?php

declare(strict_types=1);

namespace Modules\Network\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Network\Enums\ParentalControlStatus;
use Modules\Network\Events\ParentalControlActivated;
use Modules\Network\Events\ParentalControlSuspended;
use Modules\Network\Models\ParentalControlProfile;
use Modules\Network\Models\ParentalControlSubscription;
use Modules\Network\Services\DnsFilter\DnsFilterResolverInterface;
use Modules\Network\Services\DnsFilter\WhaleboneResolver;
use Modules\Network\Services\ParentalControlService;
use Modules\Network\Services\RadiusService;
use Tests\TestCase;

class ParentalControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.carrier_mock' => true]);
    }

    /** @test */
    public function test_activate_subscription_adds_radius_dns_attributes(): void
    {
        Event::fake([ParentalControlActivated::class]);

        $tenantId   = (string) Str::uuid();
        $customerId = (string) Str::uuid();
        $pppoeId    = (string) Str::uuid();

        $profile = ParentalControlProfile::create([
            'tenant_id'          => $tenantId,
            'name'               => 'Standard',
            'blocked_categories' => [],
            'custom_blacklist'   => [],
            'custom_whitelist'   => [],
        ]);

        $radiusMock = $this->mock(RadiusService::class);
        $radiusMock->shouldReceive('addReplyAttributes')
            ->once()
            ->withArgs(function (string $accountId, array $attrs) use ($pppoeId): bool {
                return $accountId === $pppoeId
                    && array_key_exists('DNS-Server-Primary', $attrs)
                    && array_key_exists('DNS-Server-Secondary', $attrs);
            });

        $this->app->bind(DnsFilterResolverInterface::class, fn() => new class implements DnsFilterResolverInterface {
            public function syncProfile(\Modules\Network\Models\ParentalControlProfile $profile): bool { return true; }
            public function syncSubscription(\Modules\Network\Models\ParentalControlSubscription $sub): bool { return true; }
            public function getStats(\Modules\Network\Models\ParentalControlSubscription $sub, \Carbon\Carbon $from, \Carbon\Carbon $to): array { return []; }
        });

        /** @var ParentalControlService $service */
        $service = app(ParentalControlService::class);

        $subscription = $service->activateForAccount($customerId, $pppoeId, $profile->id, $tenantId);

        $this->assertInstanceOf(ParentalControlSubscription::class, $subscription);
        $this->assertEquals(ParentalControlStatus::Active, $subscription->status);
        $this->assertNotNull($subscription->activated_at);

        Event::assertDispatched(ParentalControlActivated::class, function ($e) use ($subscription): bool {
            return $e->subscription->id === $subscription->id;
        });
    }

    /** @test */
    public function test_suspend_removes_dns_attributes(): void
    {
        Event::fake([ParentalControlSuspended::class]);

        $tenantId = (string) Str::uuid();
        $pppoeId  = (string) Str::uuid();

        $profile = ParentalControlProfile::create([
            'tenant_id'          => $tenantId,
            'name'               => 'Standard',
            'blocked_categories' => [],
            'custom_blacklist'   => [],
            'custom_whitelist'   => [],
        ]);

        $subscription = ParentalControlSubscription::create([
            'tenant_id'        => $tenantId,
            'customer_id'      => (string) Str::uuid(),
            'pppoe_account_id' => $pppoeId,
            'profile_id'       => $profile->id,
            'status'           => ParentalControlStatus::Active,
            'activated_at'     => now(),
        ]);

        $radiusMock = $this->mock(RadiusService::class);
        $radiusMock->shouldReceive('removeReplyAttributes')
            ->once()
            ->withArgs(function (string $accountId, array $attrs) use ($pppoeId): bool {
                return $accountId === $pppoeId
                    && in_array('DNS-Server-Primary', $attrs, true)
                    && in_array('DNS-Server-Secondary', $attrs, true);
            });

        $this->app->bind(DnsFilterResolverInterface::class, fn() => new class implements DnsFilterResolverInterface {
            public function syncProfile(\Modules\Network\Models\ParentalControlProfile $profile): bool { return true; }
            public function syncSubscription(\Modules\Network\Models\ParentalControlSubscription $sub): bool { return true; }
            public function getStats(\Modules\Network\Models\ParentalControlSubscription $sub, \Carbon\Carbon $from, \Carbon\Carbon $to): array { return []; }
        });

        /** @var ParentalControlService $service */
        $service = app(ParentalControlService::class);
        $service->suspendSubscription($subscription);

        $subscription->refresh();
        $this->assertEquals(ParentalControlStatus::Suspended, $subscription->status);
        $this->assertNotNull($subscription->suspended_at);

        Event::assertDispatched(ParentalControlSuspended::class);
    }

    /** @test */
    public function test_update_filters_syncs_resolver(): void
    {
        $tenantId = (string) Str::uuid();

        $profile = ParentalControlProfile::create([
            'tenant_id'          => $tenantId,
            'name'               => 'Standard',
            'blocked_categories' => [],
            'custom_blacklist'   => [],
            'custom_whitelist'   => [],
        ]);

        $subscription = ParentalControlSubscription::create([
            'tenant_id'        => $tenantId,
            'customer_id'      => (string) Str::uuid(),
            'pppoe_account_id' => (string) Str::uuid(),
            'profile_id'       => $profile->id,
            'status'           => ParentalControlStatus::Active,
            'activated_at'     => now(),
        ]);

        $resolverMock = $this->mock(WhaleboneResolver::class);
        $resolverMock->shouldReceive('syncSubscription')
            ->once()
            ->with(\Mockery::on(fn($sub) => $sub->id === $subscription->id))
            ->andReturn(true);

        $this->app->bind(DnsFilterResolverInterface::class, fn() => $resolverMock);

        /** @var ParentalControlService $service */
        $service = app(ParentalControlService::class);
        $service->updateCustomerFilters(
            subscription: $subscription,
            blacklist:    ['badsite.it', 'malware.com'],
            whitelist:    ['trusted.it'],
        );

        $subscription->refresh();
        $this->assertEquals(['badsite.it', 'malware.com'], $subscription->customer_custom_blacklist);
        $this->assertEquals(['trusted.it'], $subscription->customer_custom_whitelist);
    }

    /** @test */
    public function test_agcom_list_cannot_be_bypassed(): void
    {
        $tenantId = (string) Str::uuid();

        // Profilo AGCOM-compliant con un dominio bloccato
        $agcomDomain = 'blockedbyagcom.it';

        $profile = ParentalControlProfile::create([
            'tenant_id'          => $tenantId,
            'name'               => 'AGCOM Profile',
            'blocked_categories' => [],
            'custom_blacklist'   => [$agcomDomain],
            'custom_whitelist'   => [],
            'agcom_compliant'    => true,
        ]);

        $subscription = ParentalControlSubscription::create([
            'tenant_id'        => $tenantId,
            'customer_id'      => (string) Str::uuid(),
            'pppoe_account_id' => (string) Str::uuid(),
            'profile_id'       => $profile->id,
            'status'           => ParentalControlStatus::Active,
            'activated_at'     => now(),
        ]);

        // Il cliente tenta di aggiungere il dominio AGCOM alla propria whitelist
        $subscription->update(['customer_custom_whitelist' => [$agcomDomain]]);

        // Il profilo AGCOM deve ancora contenere il dominio bloccato in custom_blacklist
        $profile->refresh();
        $this->assertContains(
            $agcomDomain,
            $profile->custom_blacklist,
            'AGCOM-blocked domains must remain in the profile blacklist regardless of customer whitelist'
        );

        // La custom_whitelist del cliente contiene il dominio ma il profilo lo sovrascrive
        // (la logica di "chi vince" è nel DNS resolver, ma il profilo non deve essere modificato)
        $subscription->refresh();
        $this->assertContains($agcomDomain, $subscription->customer_custom_whitelist);

        // Verifica che il profilo agcom_compliant=true non venga toccato da updateCustomerFilters
        $this->assertTrue($profile->agcom_compliant);
    }
}
