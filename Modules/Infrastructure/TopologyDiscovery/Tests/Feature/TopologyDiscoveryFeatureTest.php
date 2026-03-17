<?php
namespace Modules\Infrastructure\TopologyDiscovery\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Infrastructure\TopologyDiscovery\Models\TopologyDiscoveryCandidate;
class TopologyDiscoveryFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_run_discovery_creates_run(): void {
        config(['app.carrier_mock'=>true]);
        $this->actingAsAdmin()->postJson('/api/topology/discovery/run')->assertStatus(201)->assertJsonPath('status','completed');
    }
    public function test_confirm_candidate_creates_topology_link(): void {
        config(['app.carrier_mock'=>true]);
        $src = \Illuminate\Support\Str::uuid();
        $tgt = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('hardware_devices')->insert([['id'=>$src,'name'=>'Dev1','mac_address'=>'aa:00:00:00:00:01','status'=>'online'],['id'=>$tgt,'name'=>'Dev2','mac_address'=>'bb:00:00:00:00:01','status'=>'online']]);
        $run = \Illuminate\Support\Facades\DB::table('topology_discovery_runs')->insertGetId(['id'=>\Illuminate\Support\Str::uuid(),'status'=>'completed','started_at'=>now(),'devices_scanned'=>1,'links_discovered'=>1,'links_confirmed'=>0,'links_removed'=>0]);
        $candidate = TopologyDiscoveryCandidate::create(['discovery_run_id'=>\Illuminate\Support\Facades\DB::table('topology_discovery_runs')->latest()->first()->id,'source_device_id'=>$src,'target_mac'=>'bb:00:00:00:00:01','source_interface'=>'ether1','matched_device_id'=>$tgt,'discovery_method'=>'lldp','status'=>'pending']);
        $this->actingAsAdmin()->postJson("/api/topology/discovery/candidates/{$candidate->id}/confirm")->assertOk();
        $this->assertDatabaseHas('topology_links', ['source_device_id'=>$src,'target_device_id'=>$tgt]);
        $this->assertEquals('confirmed', TopologyDiscoveryCandidate::find($candidate->id)->status->value);
    }
}
