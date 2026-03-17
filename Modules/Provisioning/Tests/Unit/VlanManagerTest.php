<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Unit;

use Modules\Provisioning\Models\VlanPool;
use Modules\Provisioning\Services\VlanManager;
use Tests\TestCase;

class VlanManagerTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private VlanManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new VlanManager();
    }

    /** @test */
    public function it_assigns_first_free_vlan(): void
    {
        $this->seedVlans('openfiber', 100, 110);
        $contract = \Modules\Contracts\Models\Contract::factory()->create(['tenant_id' => 1]);

        $vlan = $this->manager->assign('openfiber', $contract);

        $this->assertEquals('assigned', $vlan->status);
        $this->assertEquals($contract->id, $vlan->contract_id);
        $this->assertNotNull($vlan->assigned_at);
    }

    /** @test */
    public function it_throws_when_no_vlan_available(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Nessun C-VLAN libero/');

        $contract = \Modules\Contracts\Models\Contract::factory()->create(['tenant_id' => 1]);
        $this->manager->assign('openfiber', $contract);
    }

    /** @test */
    public function it_releases_vlan_on_contract_termination(): void
    {
        $this->seedVlans('openfiber', 100, 100);
        $contract = \Modules\Contracts\Models\Contract::factory()->create(['tenant_id' => 1]);

        $vlan = $this->manager->assign('openfiber', $contract);
        $this->assertEquals('assigned', $vlan->fresh()->status);

        $this->manager->release($contract);

        $this->assertEquals('free', $vlan->fresh()->status);
        $this->assertNull($vlan->fresh()->contract_id);
    }

    /** @test */
    public function it_counts_available_vlans(): void
    {
        $this->seedVlans('openfiber', 100, 110); // 11 VLAN

        $available = $this->manager->getAvailable(1, 'openfiber');
        $this->assertEquals(11, $available);
    }

    /** @test */
    public function concurrent_assign_does_not_give_same_vlan(): void
    {
        $this->seedVlans('openfiber', 100, 101); // solo 2 VLAN

        $contract1 = \Modules\Contracts\Models\Contract::factory()->create(['tenant_id' => 1]);
        $contract2 = \Modules\Contracts\Models\Contract::factory()->create(['tenant_id' => 1]);

        $vlan1 = $this->manager->assign('openfiber', $contract1);
        $vlan2 = $this->manager->assign('openfiber', $contract2);

        $this->assertNotEquals($vlan1->vlan_id, $vlan2->vlan_id);
    }

    private function seedVlans(string $carrier, int $from, int $to): void
    {
        for ($i = $from; $i <= $to; $i++) {
            VlanPool::create([
                'tenant_id' => 1,
                'carrier'   => $carrier,
                'vlan_type' => 'C-VLAN',
                'vlan_id'   => $i,
                'status'    => 'free',
            ]);
        }
    }
}
