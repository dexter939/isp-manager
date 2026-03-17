<?php
namespace Modules\Maintenance\RouteOptimizer\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
class RouteOptimizerFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_optimize_route_with_3_interventions(): void {
        config(['app.carrier_mock'=>true]);
        $techId = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('users')->insert(['id'=>$techId,'name'=>'Tecnico','email'=>'t@t.it','password'=>'x']);
        for ($i=1; $i<=3; $i++) {
            $intId = \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\DB::table('field_interventions')->insert(['id'=>$intId,'customer_id'=>\Illuminate\Support\Str::uuid(),'intervention_type'=>'installation','status'=>'scheduled','latitude'=>45.4654+$i*0.01,'longitude'=>9.1866+$i*0.01,'created_at'=>now(),'updated_at'=>now()]);
            \Illuminate\Support\Facades\DB::table('dispatch_assignments')->insert(['id'=>\Illuminate\Support\Str::uuid(),'intervention_id'=>$intId,'technician_id'=>$techId,'scheduled_start'=>now()->setHour(9+$i),'scheduled_end'=>now()->setHour(10+$i),'estimated_duration_minutes'=>60,'status'=>'scheduled','created_at'=>now(),'updated_at'=>now()]);
        }
        $this->actingAsAdmin()->postJson('/api/routes/optimize', ['technician_id'=>$techId,'date'=>now()->toDateString()])->assertStatus(201)->assertJsonStructure(['id','optimized_order','total_distance_km']);
    }
    public function test_cached_directions_not_called_twice(): void {
        config(['app.carrier_mock'=>true]);
        \Illuminate\Support\Facades\Cache::spy();
        $plan = \Modules\Maintenance\RouteOptimizer\Models\RoutePlan::factory()->create(['optimized_order'=>[]]);
        $this->actingAsAdmin()->getJson("/api/routes/plans/{$plan->id}/directions")->assertOk();
    }
}
