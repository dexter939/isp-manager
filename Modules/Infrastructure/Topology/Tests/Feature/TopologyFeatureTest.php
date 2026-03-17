<?php
namespace Modules\Infrastructure\Topology\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Infrastructure\Topology\Models\TopologyLink;
class TopologyFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_create_topology_link(): void {
        $src = \Illuminate\Support\Str::uuid();
        $tgt = \Illuminate\Support\Str::uuid();
        $this->actingAsAdmin()->postJson('/api/topology/links', ['source_device_id'=>$src,'target_device_id'=>$tgt,'link_type'=>'fiber','bandwidth_mbps'=>1000])->assertStatus(201)->assertJsonPath('link_type','fiber');
    }
    public function test_device_impact_returns_downstream(): void {
        $src = \Illuminate\Support\Str::uuid();
        $tgt = \Illuminate\Support\Str::uuid();
        TopologyLink::factory()->create(['source_device_id'=>$src,'target_device_id'=>$tgt]);
        $this->actingAsAdmin()->getJson("/api/topology/devices/{$src}/impact")->assertOk()->assertJsonPath('impacted_count', 1);
    }
}
