<?php
namespace Modules\Infrastructure\Topology\Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Infrastructure\Topology\Models\TopologyLink;
use Modules\Infrastructure\Topology\Services\TopologyService;
class TopologyServiceTest extends TestCase {
    use RefreshDatabase;
    private TopologyService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new TopologyService(); }
    public function test_get_impacted_devices_3_levels(): void {
        // router → switch1 → ap1
        //                  → ap2
        $router  = 'aaa00000-0000-0000-0000-000000000001';
        $switch1 = 'bbb00000-0000-0000-0000-000000000001';
        $ap1     = 'ccc00000-0000-0000-0000-000000000001';
        $ap2     = 'ccc00000-0000-0000-0000-000000000002';
        TopologyLink::factory()->create(['source_device_id'=>$router,'target_device_id'=>$switch1]);
        TopologyLink::factory()->create(['source_device_id'=>$switch1,'target_device_id'=>$ap1]);
        TopologyLink::factory()->create(['source_device_id'=>$switch1,'target_device_id'=>$ap2]);
        $impacted = $this->service->getImpactedDevices($router);
        $this->assertCount(3, $impacted);
        $this->assertContains($switch1, $impacted->toArray());
        $this->assertContains($ap1, $impacted->toArray());
        $this->assertContains($ap2, $impacted->toArray());
    }
    public function test_impacted_devices_leaf_node_has_no_downstream(): void {
        $leafId = 'ddd00000-0000-0000-0000-000000000001';
        $impacted = $this->service->getImpactedDevices($leafId);
        $this->assertCount(0, $impacted);
    }
    public function test_empty_graph_returns_empty_nodes_and_edges(): void {
        $graph = $this->service->getGraph('nonexistent-site');
        $this->assertEmpty($graph['nodes']);
        $this->assertEmpty($graph['edges']);
    }
}
