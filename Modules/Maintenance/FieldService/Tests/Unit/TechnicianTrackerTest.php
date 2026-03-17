<?php
namespace Modules\Maintenance\FieldService\Tests\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Maintenance\FieldService\Models\TechnicianPosition;
use Modules\Maintenance\FieldService\Services\TechnicianTracker;
use Tests\TestCase;

class TechnicianTrackerTest extends TestCase {
    use RefreshDatabase;

    public function test_stores_position_in_redis(): void {
        $tracker = app(TechnicianTracker::class);
        $tracker->updatePosition(1, 45.4654, 9.1866, 10);
        $pos = Cache::get('tech:pos:1');
        $this->assertNotNull($pos);
        $this->assertEquals(45.4654, $pos['lat']);
    }

    public function test_retrieves_latest_position_from_redis(): void {
        $tracker = app(TechnicianTracker::class);
        $tracker->updatePosition(42, 41.8919, 12.5113);
        $pos = $tracker->getLatestPosition(42);
        $this->assertNotNull($pos);
        $this->assertEquals(41.8919, $pos['lat']);
    }

    public function test_cleanup_removes_old_records(): void {
        TechnicianPosition::factory()->count(5)->create(['recorded_at' => now()->subDays(40)]);
        TechnicianPosition::factory()->count(3)->create(['recorded_at' => now()->subDays(5)]);
        $tracker = app(TechnicianTracker::class);
        $deleted = $tracker->cleanup();
        $this->assertEquals(5, $deleted);
        $this->assertEquals(3, TechnicianPosition::count());
    }
}
