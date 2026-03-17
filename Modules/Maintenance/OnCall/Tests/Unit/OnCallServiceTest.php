<?php
namespace Modules\Maintenance\OnCall\Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Maintenance\OnCall\Services\OnCallService;
use Modules\Maintenance\OnCall\Models\OncallSchedule;
use Modules\Maintenance\OnCall\Models\OncallScheduleSlot;
use Carbon\Carbon;
class OnCallServiceTest extends TestCase {
    use RefreshDatabase;
    private OnCallService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new OnCallService(); }
    public function test_get_current_oncall_active_slot(): void {
        $schedule = OncallSchedule::factory()->create();
        $userId   = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('users')->insert(['id'=>$userId,'name'=>'Tech 1','email'=>'tech@test.it','password'=>'hash']);
        OncallScheduleSlot::factory()->create(['schedule_id'=>$schedule->id,'user_id'=>$userId,'level'=>1,'start_datetime'=>now()->subHours(2),'end_datetime'=>now()->addHours(6)]);
        $result = $this->service->getCurrentOnCall(now());
        $this->assertNotNull($result);
        $this->assertEquals($userId, $result->user_id);
    }
    public function test_no_oncall_outside_schedule(): void {
        $result = $this->service->getCurrentOnCall(now());
        $this->assertNull($result);
    }
    public function test_oncall_between_shifts_no_match(): void {
        $schedule = OncallSchedule::factory()->create();
        $userId   = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('users')->insert(['id'=>$userId,'name'=>'Tech','email'=>'t@t.it','password'=>'x']);
        // Shift ended 1 hour ago
        OncallScheduleSlot::factory()->create(['schedule_id'=>$schedule->id,'user_id'=>$userId,'level'=>1,'start_datetime'=>now()->subHours(9),'end_datetime'=>now()->subHours(1)]);
        $result = $this->service->getCurrentOnCall(now());
        $this->assertNull($result);
    }
}
