<?php
namespace Modules\Maintenance\OnCall\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Maintenance\OnCall\Models\OncallSchedule;
use Modules\Maintenance\OnCall\Models\OncallScheduleSlot;
use Modules\Maintenance\OnCall\Models\OncallAlertDispatch;
class OnCallFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_current_returns_null_when_no_schedule(): void {
        $this->actingAsAdmin()->getJson('/api/oncall/current')->assertOk()->assertJsonPath('on_call', null);
    }
    public function test_current_returns_technician_when_scheduled(): void {
        $schedule = OncallSchedule::factory()->create();
        $userId   = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('users')->insert(['id'=>$userId,'name'=>'Tecnico','email'=>'tec@test.it','password'=>'x']);
        OncallScheduleSlot::factory()->create(['schedule_id'=>$schedule->id,'user_id'=>$userId,'level'=>1,'start_datetime'=>now()->subHour(),'end_datetime'=>now()->addHours(8)]);
        $this->actingAsAdmin()->getJson('/api/oncall/current')->assertOk()->assertJsonStructure(['slot','user']);
    }
    public function test_escalation_job_dispatched_on_alert(): void {
        \Illuminate\Support\Facades\Queue::fake();
        $schedule = OncallSchedule::factory()->create(['escalation_timeout_minutes'=>15]);
        $userId   = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('users')->insert(['id'=>$userId,'name'=>'T','email'=>'t@t.it','password'=>'x']);
        OncallScheduleSlot::factory()->create(['schedule_id'=>$schedule->id,'user_id'=>$userId,'level'=>1,'start_datetime'=>now()->subHour(),'end_datetime'=>now()->addHours(8)]);
        \Illuminate\Support\Facades\DB::table('monitoring_alerts')->insert(['id'=>\Illuminate\Support\Str::uuid(),'device_id'=>\Illuminate\Support\Str::uuid(),'type'=>'down','status'=>'open','message'=>'Device offline','created_at'=>now()]);
        $alert = \Illuminate\Support\Facades\DB::table('monitoring_alerts')->latest()->first();
        (new \Modules\Maintenance\OnCall\Services\OnCallService())->dispatchAlert($alert);
        \Illuminate\Support\Facades\Queue::assertPushed(\Modules\Maintenance\OnCall\Jobs\EscalateAlertJob::class);
    }
}
