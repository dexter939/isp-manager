<?php
namespace Modules\Infrastructure\TopologyDiscovery\Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Infrastructure\TopologyDiscovery\Services\LldpDiscoveryScanner;
class LldpDiscoveryScannerTest extends TestCase {
    use RefreshDatabase;
    private LldpDiscoveryScanner $scanner;
    protected function setUp(): void { parent::setUp(); $this->scanner = new LldpDiscoveryScanner(); config(['app.carrier_mock'=>true]); }
    public function test_mock_lldp_returns_neighbor(): void {
        $device = (object)['id'=>'dev-1','ip_address'=>'10.0.0.1','snmp_community'=>'public'];
        $result = $this->scanner->scan($device);
        $this->assertNotEmpty($result);
        $this->assertEquals('dev-1', $result[0]['source_device_id']);
        $this->assertEquals('lldp', $result[0]['discovery_method']);
    }
    public function test_match_mac_to_device_not_found(): void {
        $result = $this->scanner->matchMacToDevice('ff:ff:ff:ff:ff:ff');
        $this->assertNull($result);
    }
    public function test_match_mac_to_device_found(): void {
        \Illuminate\Support\Facades\DB::table('hardware_devices')->insert(['id'=>\Illuminate\Support\Str::uuid(),'name'=>'Test Switch','mac_address'=>'aa:bb:cc:dd:ee:01','status'=>'online']);
        $result = $this->scanner->matchMacToDevice('aa:bb:cc:dd:ee:01');
        $this->assertNotNull($result);
        $this->assertEquals('Test Switch', $result->name);
    }
}
