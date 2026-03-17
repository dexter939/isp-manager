<?php

namespace Modules\Contracts\WizardMobile\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contracts\WizardMobile\Models\ContractWizardSession;
use Modules\Contracts\WizardMobile\Services\WizardSessionService;
use Tests\TestCase;

class WizardMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_wizard_session(): void
    {
        $agent = $this->createUser(['role' => 'agent']);

        $response = $this->actingAs($agent)->postJson('/api/wizard/sessions');

        $response->assertStatus(201);
        $this->assertDatabaseHas('contract_wizard_sessions', [
            'agent_id' => $agent->id,
            'status'   => 'in_progress',
        ]);
    }

    public function test_saves_step_data_progressively(): void
    {
        $agent   = $this->createUser(['role' => 'agent']);
        $service = app(WizardSessionService::class);
        $session = $service->create($agent->id);

        $session = $service->saveStep($session, 0, [
            'first_name' => 'Mario',
            'last_name'  => 'Rossi',
            'email'      => 'mario@example.com',
            'telefono'   => '+393331234567',
        ]);

        $this->assertEquals(1, $session->current_step);
        $this->assertEquals('Mario', $session->step_data['cliente']['first_name'] ?? null);
    }

    public function test_sends_and_verifies_otp(): void
    {
        config(['app.carrier_mock' => true]);

        $service = app(WizardSessionService::class);
        $session = $service->create(1);
        $session->update(['step_data' => ['cliente' => ['telefono' => '+393331234567']]]);

        $service->sendOtp($session);
        $session->refresh();
        $this->assertNotNull($session->otp_code);

        // In mock mode, OTP is '000000'
        $verified = $service->verifyOtp($session, '000000');
        $this->assertTrue($verified);
    }

    public function test_cannot_finalize_without_otp_verification(): void
    {
        $service = app(WizardSessionService::class);
        $session = $service->create(1);

        $this->expectException(\RuntimeException::class);
        $service->finalizeContract($session);
    }
}
