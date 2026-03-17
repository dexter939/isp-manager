<?php
namespace Modules\Maintenance\FieldService\Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Maintenance\FieldService\Models\FieldIntervention;
use Modules\Maintenance\FieldService\Services\FieldInterventionService;
use Tests\TestCase;

class FieldInterventionTest extends TestCase {
    use RefreshDatabase;

    public function test_schedules_intervention(): void {
        $user = $this->createUser();
        $technician = $this->createUser(['role' => 'technician']);
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->postJson('/api/field/interventions', [
            'customer_id' => $user->id,
            'intervention_type' => 'repair',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'address' => 'Via Roma 1, Milano',
            'technician_id' => $technician->id,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('field_interventions', ['customer_id' => $user->id]);
    }

    public function test_starts_intervention_with_position(): void {
        $intervention = FieldIntervention::factory()->create(['status' => 'scheduled']);
        $service = app(FieldInterventionService::class);
        $service->startIntervention($intervention, 45.4654, 9.1866);
        $this->assertEquals('in_progress', $intervention->fresh()->status);
    }

    public function test_customer_signature_with_otp(): void {
        config(['app.carrier_mock' => true]);
        $intervention = FieldIntervention::factory()->create(['status' => 'in_progress']);
        $user = $this->createUser();
        $response = $this->actingAs($user)->postJson("/api/field/interventions/{$intervention->uuid}/sign/otp", ['phone' => '+393331234567']);
        $response->assertOk();
        $otp = $response->json('otp');
        $this->assertNotNull($otp);
        $pngBase64 = 'data:image/png;base64,' . base64_encode('fakepng');
        $signResponse = $this->actingAs($user)->postJson("/api/field/interventions/{$intervention->uuid}/sign", [
            'otp' => $otp,
            'signature_base64' => $pngBase64,
            'signer_name' => 'Mario Rossi',
        ]);
        $signResponse->assertOk();
        $this->assertDatabaseHas('field_signatures', ['intervention_id' => $intervention->id]);
    }
}
