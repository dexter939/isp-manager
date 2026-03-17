<?php
namespace Modules\Coverage\Elevation\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
class ElevationFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_calculate_elevation_profile_with_mock(): void {
        config(['app.carrier_mock'=>true]);
        $siteId = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('network_sites')->insert(['id'=>$siteId,'name'=>'BTS Test','type'=>'mast','latitude'=>45.4654,'longitude'=>9.1866,'status'=>'active','importance'=>'normal','created_at'=>now(),'updated_at'=>now()]);
        $this->actingAsAdmin()->postJson('/api/elevation/calculate', ['network_site_id'=>$siteId,'customer_lat'=>45.4700,'customer_lon'=>9.1900,'antenna_height_m'=>15,'cpe_height_m'=>3,'frequency_ghz'=>5.8])->assertStatus(201)->assertJsonStructure(['id','distance_km','has_obstruction','profile_data']);
    }
    public function test_second_calculation_uses_cache(): void {
        config(['app.carrier_mock'=>true]);
        $siteId = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('network_sites')->insert(['id'=>$siteId,'name'=>'BTS Test','type'=>'mast','latitude'=>45.4654,'longitude'=>9.1866,'status'=>'active','importance'=>'normal','created_at'=>now(),'updated_at'=>now()]);
        Http::fake(['*' => Http::response(['results'=>[['elevation'=>300]]],200)]);
        // First call
        $r1 = $this->actingAsAdmin()->postJson('/api/elevation/calculate', ['network_site_id'=>$siteId,'customer_lat'=>45.5,'customer_lon'=>9.2])->assertStatus(201)->json('id');
        // Second call — should return same cached profile
        $r2 = $this->actingAsAdmin()->postJson('/api/elevation/calculate', ['network_site_id'=>$siteId,'customer_lat'=>45.5,'customer_lon'=>9.2])->assertStatus(201)->json('id');
        $this->assertEquals($r1, $r2, "Second calculation should return cached profile with same ID");
    }
}
