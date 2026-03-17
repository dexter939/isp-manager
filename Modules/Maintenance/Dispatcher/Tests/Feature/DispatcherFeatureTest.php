<?php

declare(strict_types=1);

namespace Modules\Maintenance\Dispatcher\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Maintenance\Dispatcher\Models\DispatchAssignment;
use Tests\TestCase;

class DispatcherFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $techId;
    private string $interventionId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.carrier_mock' => true]);
        $this->techId         = Str::uuid()->toString();
        $this->interventionId = $this->createFieldIntervention();
    }

    /** @test */
    public function it_creates_assignment_without_conflict(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [
                'intervention_id'            => $this->interventionId,
                'technician_id'              => $this->techId,
                'scheduled_start'            => '2025-06-10 09:00',
                'estimated_duration_minutes' => 120,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'scheduled');
    }

    /** @test */
    public function it_computes_scheduled_end_from_duration(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [
                'intervention_id'            => $this->interventionId,
                'technician_id'              => $this->techId,
                'scheduled_start'            => '2025-06-10 09:00',
                'estimated_duration_minutes' => 90,
            ])
            ->assertCreated()
            ->assertJsonPath('scheduled_end', '2025-06-10 10:30:00');
    }

    /** @test */
    public function it_rejects_overlapping_assignment_for_same_technician(): void
    {
        DispatchAssignment::factory()->create([
            'tenant_id'       => 1,
            'technician_id'   => $this->techId,
            'scheduled_start' => '2025-06-10 09:00:00',
            'scheduled_end'   => '2025-06-10 11:00:00',
            'status'          => 'scheduled',
        ]);
        $second = $this->createFieldIntervention();

        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [
                'intervention_id'            => $second,
                'technician_id'              => $this->techId,
                'scheduled_start'            => '2025-06-10 10:00',
                'estimated_duration_minutes' => 120,
            ])
            ->assertUnprocessable();
    }

    /** @test */
    public function it_allows_back_to_back_assignments(): void
    {
        DispatchAssignment::factory()->create([
            'tenant_id'       => 1,
            'technician_id'   => $this->techId,
            'scheduled_start' => '2025-06-10 09:00:00',
            'scheduled_end'   => '2025-06-10 11:00:00',
            'status'          => 'scheduled',
        ]);
        $second = $this->createFieldIntervention();

        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [
                'intervention_id'            => $second,
                'technician_id'              => $this->techId,
                'scheduled_start'            => '2025-06-10 11:00',
                'estimated_duration_minutes' => 60,
            ])
            ->assertCreated();
    }

    /** @test */
    public function it_allows_overlap_for_different_technician(): void
    {
        DispatchAssignment::factory()->create([
            'tenant_id'       => 1,
            'technician_id'   => $this->techId,
            'scheduled_start' => '2025-06-10 09:00:00',
            'scheduled_end'   => '2025-06-10 11:00:00',
            'status'          => 'scheduled',
        ]);
        $second = $this->createFieldIntervention();

        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [
                'intervention_id'            => $second,
                'technician_id'              => Str::uuid()->toString(),
                'scheduled_start'            => '2025-06-10 10:00',
                'estimated_duration_minutes' => 120,
            ])
            ->assertCreated();
    }

    /** @test */
    public function it_ignores_cancelled_assignments_in_conflict_check(): void
    {
        DispatchAssignment::factory()->create([
            'tenant_id'       => 1,
            'technician_id'   => $this->techId,
            'scheduled_start' => '2025-06-10 09:00:00',
            'scheduled_end'   => '2025-06-10 11:00:00',
            'status'          => 'cancelled',
        ]);

        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [
                'intervention_id'            => $this->interventionId,
                'technician_id'              => $this->techId,
                'scheduled_start'            => '2025-06-10 09:30',
                'estimated_duration_minutes' => 60,
            ])
            ->assertCreated();
    }

    /** @test */
    public function it_returns_daily_timeline(): void
    {
        DispatchAssignment::factory()->create([
            'tenant_id'       => 1,
            'technician_id'   => $this->techId,
            'scheduled_start' => '2025-06-10 09:00:00',
            'scheduled_end'   => '2025-06-10 10:00:00',
            'status'          => 'scheduled',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/dispatcher/timeline/2025-06-10')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_lists_unassigned_interventions(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/dispatcher/unassigned')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'intervention_id', 'technician_id', 'scheduled_start', 'estimated_duration_minutes',
            ]);
    }

    /** @test */
    public function it_rejects_duration_below_minimum(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/dispatcher/assignments', [
                'intervention_id'            => $this->interventionId,
                'technician_id'              => $this->techId,
                'scheduled_start'            => '2025-06-10 09:00',
                'estimated_duration_minutes' => 5,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estimated_duration_minutes']);
    }
}
