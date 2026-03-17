<?php
namespace Modules\Infrastructure\NetworkSites\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Infrastructure\NetworkSites\Models\NetworkSite;
class NetworkSiteTest extends TestCase {
    use RefreshDatabase;
    public function test_crud_network_site(): void {
        $resp = $this->actingAsAdmin()->postJson('/api/network-sites', ['name'=>'POP Roma Centro','type'=>'pop','address'=>'Via Roma 1, Roma','latitude'=>41.9028,'longitude'=>12.4964,'status'=>'active','importance'=>'critical'])->assertStatus(201)->assertJsonPath('name','POP Roma Centro');
        $id = $resp->json('id');
        $this->actingAsAdmin()->getJson("/api/network-sites/{$id}")->assertOk()->assertJsonPath('type','pop');
        $this->actingAsAdmin()->putJson("/api/network-sites/{$id}", ['status'=>'maintenance'])->assertOk()->assertJsonPath('status','maintenance');
        $this->actingAsAdmin()->deleteJson("/api/network-sites/{$id}")->assertStatus(204);
    }
    public function test_link_hardware_to_site(): void {
        $site = NetworkSite::factory()->create();
        $hwId = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('hardware_devices')->insert(['id'=>$hwId,'name'=>'Switch','status'=>'online']);
        $this->actingAsAdmin()->postJson("/api/network-sites/{$site->id}/hardware", ['hardware_id'=>$hwId,'is_access_device'=>true])->assertOk();
        $this->actingAsAdmin()->getJson("/api/network-sites/{$site->id}/hardware")->assertOk()->assertJsonCount(1);
    }
    public function test_bulk_link_customer_services(): void {
        $site  = NetworkSite::factory()->create();
        $hwId  = \Illuminate\Support\Str::uuid();
        $c1    = \Illuminate\Support\Str::uuid();
        $c2    = \Illuminate\Support\Str::uuid();
        $this->actingAsAdmin()->postJson("/api/network-sites/{$site->id}/customer-services/bulk", ['hardware_id'=>$hwId,'contract_ids'=>[$c1,$c2]])->assertOk()->assertJsonPath('linked', 2);
    }
    public function test_map_returns_only_sites_with_coordinates(): void {
        NetworkSite::factory()->create(['latitude'=>null,'longitude'=>null]);
        NetworkSite::factory()->create(['latitude'=>45.4654,'longitude'=>9.1866,'status'=>'active']);
        $this->actingAsAdmin()->getJson('/api/network-sites/map')->assertOk()->assertJsonCount(1);
    }
}
