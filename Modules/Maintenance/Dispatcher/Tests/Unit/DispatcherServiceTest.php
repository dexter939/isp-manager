<?php
namespace Modules\Maintenance\Dispatcher\Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Maintenance\Dispatcher\Services\DispatcherService;
use Modules\Maintenance\Dispatcher\Models\DispatchAssignment;
use Carbon\Carbon;
class DispatcherServiceTest extends TestCase {
    use RefreshDatabase;
    private DispatcherService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new DispatcherService(); }
    public function test_conflict_detected_partial_overlap(): void {
        $techId = \Illuminate\Support\Str::uuid();
        DispatchAssignment::factory()->create(['technician_id'=>$techId,'scheduled_start'=>Carbon::parse('2024-01-15 09:00'),'scheduled_end'=>Carbon::parse('2024-01-15 11:00'),'status'=>'scheduled']);
        $hasConflict = $this->service->checkConflict($techId, Carbon::parse('2024-01-15 10:00'), Carbon::parse('2024-01-15 12:00'));
        $this->assertTrue($hasConflict);
    }
    public function test_no_conflict_adjacent_slots(): void {
        $techId = \Illuminate\Support\Str::uuid();
        DispatchAssignment::factory()->create(['technician_id'=>$techId,'scheduled_start'=>Carbon::parse('2024-01-15 09:00'),'scheduled_end'=>Carbon::parse('2024-01-15 11:00'),'status'=>'scheduled']);
        $hasConflict = $this->service->checkConflict($techId, Carbon::parse('2024-01-15 11:00'), Carbon::parse('2024-01-15 13:00'));
        $this->assertFalse($hasConflict);
    }
    public function test_assign_throws_on_conflict(): void {
        $techId = \Illuminate\Support\Str::uuid();
        DispatchAssignment::factory()->create(['technician_id'=>$techId,'scheduled_start'=>Carbon::parse('2024-01-15 09:00'),'scheduled_end'=>Carbon::parse('2024-01-15 11:00'),'status'=>'scheduled']);
        $this->expectException(\RuntimeException::class);
        $this->service->assign(\Illuminate\Support\Str::uuid(), $techId, Carbon::parse('2024-01-15 10:00'), 120);
    }
}
