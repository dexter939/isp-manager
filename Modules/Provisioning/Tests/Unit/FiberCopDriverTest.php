<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Provisioning\Services\Drivers\FiberCopDriver;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Testa FiberCopDriver con Http::fake() — nessuna chiamata reale al carrier.
 *
 * Copre:
 *   - OAuth2 token acquisition e caching
 *   - Token refresh su 401
 *   - statusZpoint parsing dal fixture
 *   - Risposta NGASP per invio ordine attivazione
 */
class FiberCopDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.carrier_mock'                 => false,
            'provisioning.fibercop.client_key'     => 'test-key',
            'provisioning.fibercop.client_secret'  => 'test-secret',
            'provisioning.fibercop.token_endpoint' => 'https://api.fibercop.it/oauth/token',
            'provisioning.fibercop.api_base_url'   => 'https://api.fibercop.it/ngasp/v1',
        ]);
    }

    /** @test */
    public function it_fetches_oauth_token_on_first_call(): void
    {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-access-token-abc',
                'expires_in'   => 3600,
                'token_type'   => 'Bearer',
            ], 200),

            '*/zpoint/IT-RM-0001-23456/status' => Http::response(
                json_decode(file_get_contents(base_path('tests/Fixtures/fibercop_status_zpoint.json')), true),
                200
            ),
        ]);

        Cache::forget('fibercop_oauth_token');

        $driver = app(FiberCopDriver::class);
        $result = $driver->statusZpoint('IT-RM-0001-23456');

        $this->assertTrue($result->success);
        $this->assertEquals('ACTIVE', $result->operationalStatus);

        // Token deve essere cached
        $this->assertNotNull(Cache::get('fibercop_oauth_token'));
    }

    /** @test */
    public function it_reuses_cached_token(): void
    {
        Cache::put('fibercop_oauth_token', 'cached-token-xyz', 3000);

        Http::fake([
            '*/zpoint/IT-RM-0001-TEST/status' => Http::response([
                'status'  => 'SUCCESS',
                'zpoint'  => [
                    'operationalStatus' => 'ACTIVE',
                    'adminStatus'       => 'ENABLED',
                    'registrationStatus' => 'REGISTERED',
                    'rxPowerDbm'        => -20.1,
                ],
            ], 200),
        ]);

        $driver = app(FiberCopDriver::class);
        $driver->statusZpoint('IT-RM-0001-TEST');

        // Non deve aver fatto chiamate a /oauth/token
        Http::assertNothingSent(fn($request) => str_contains($request->url(), 'oauth/token'));
    }

    /** @test */
    public function it_refreshes_token_on_401(): void
    {
        Cache::put('fibercop_oauth_token', 'expired-token', 1);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'new-token-after-refresh',
                'expires_in'   => 3600,
            ], 200),

            '*/zpoint/IT-RM-EXPIRED/status' => Http::sequence()
                ->push(null, 401)  // Prima chiamata: token scaduto
                ->push([           // Seconda chiamata: successo dopo refresh
                    'status' => 'SUCCESS',
                    'zpoint' => [
                        'operationalStatus' => 'ACTIVE',
                        'adminStatus'       => 'ENABLED',
                        'registrationStatus' => 'REGISTERED',
                        'rxPowerDbm'        => -18.0,
                    ],
                ], 200),
        ]);

        $driver = app(FiberCopDriver::class);
        $result = $driver->statusZpoint('IT-RM-EXPIRED');

        $this->assertTrue($result->success);
        $this->assertEquals('new-token-after-refresh', Cache::get('fibercop_oauth_token'));
    }

    /** @test */
    public function it_parses_zpoint_fixture_correctly(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/fibercop_status_zpoint.json')), true);

        Http::fake([
            '*/oauth/token'              => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            '*/zpoint/IT-RM-0001-23456/status' => Http::response($fixture, 200),
        ]);

        Cache::forget('fibercop_oauth_token');

        $driver = app(FiberCopDriver::class);
        $result = $driver->statusZpoint('IT-RM-0001-23456');

        $this->assertEquals('ACTIVE', $result->operationalStatus);
        $this->assertEquals('REGISTERED', $result->registrationStatus);
        $this->assertEquals(-19.2, $result->rxPowerDbm);
        $this->assertEquals('ALCL12345678', $result->ontSerialNumber);
    }

    /** @test */
    public function mocked_driver_skips_http_calls(): void
    {
        config(['app.carrier_mock' => true]);

        Http::fake(); // nessuna risposta configurata — fallisce se chiamato

        $driver = app(FiberCopDriver::class);
        $response = $driver->sendActivationOrder(
            \Modules\Provisioning\Models\CarrierOrder::factory()->make()
        );

        $this->assertTrue($response->success);
        Http::assertNothingSent();
    }
}
