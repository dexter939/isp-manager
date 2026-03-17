<?php
namespace Modules\Infrastructure\TopologyDiscovery\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Infrastructure\TopologyDiscovery\Models\TopologyDiscoveryRun;
use Modules\Infrastructure\TopologyDiscovery\Models\TopologyDiscoveryCandidate;
class TopologyDiscoveryService {
    public function __construct(private LldpDiscoveryScanner $lldpScanner) {}
    public function runDiscovery(): TopologyDiscoveryRun {
        $run = TopologyDiscoveryRun::create(['status'=>'running','started_at'=>now()]);
        $devices = DB::table('hardware_devices')->where('snmp_enabled', true)->where('status','online')->get();
        $linksDiscovered = 0;
        foreach ($devices as $device) {
            $neighbors = $this->lldpScanner->scan($device);
            foreach ($neighbors as $neighbor) {
                $matchedDevice = $this->lldpScanner->matchMacToDevice($neighbor['target_mac'] ?? '');
                TopologyDiscoveryCandidate::create(['discovery_run_id'=>$run->id,'source_device_id'=>$neighbor['source_device_id'],'target_mac'=>$neighbor['target_mac'],'target_ip'=>$neighbor['target_ip'] ?? null,'target_hostname'=>$neighbor['target_hostname'] ?? null,'source_interface'=>$neighbor['source_interface'],'target_interface'=>$neighbor['target_interface'] ?? null,'discovery_method'=>$neighbor['discovery_method'],'matched_device_id'=>$matchedDevice?->id,'status'=>$matchedDevice ? 'pending' : 'pending']);
                $linksDiscovered++;
            }
        }
        $run->update(['status'=>'completed','completed_at'=>now(),'devices_scanned'=>$devices->count(),'links_discovered'=>$linksDiscovered]);
        return $run;
    }
    public function confirmCandidate(TopologyDiscoveryCandidate $candidate): object {
        if (!$candidate->matched_device_id) throw new \RuntimeException('Cannot confirm candidate without matched device');
        DB::transaction(function () use ($candidate) {
            DB::table('topology_links')->insert(['id'=>Str::uuid(),'source_device_id'=>$candidate->source_device_id,'target_device_id'=>$candidate->matched_device_id,'link_type'=>'fiber','source_interface'=>$candidate->source_interface,'target_interface'=>$candidate->target_interface,'status'=>'unknown','created_at'=>now(),'updated_at'=>now()]);
            $candidate->update(['status'=>'confirmed']);
        });
        return DB::table('topology_links')->where('source_device_id', $candidate->source_device_id)->where('target_device_id', $candidate->matched_device_id)->latest('created_at')->first();
    }
    public function rejectCandidate(TopologyDiscoveryCandidate $candidate): void {
        $candidate->update(['status'=>'rejected']);
    }
    public function bulkConfirm(array $candidateIds): int {
        $count = 0;
        foreach ($candidateIds as $id) {
            $candidate = TopologyDiscoveryCandidate::find($id);
            if ($candidate && $candidate->status->value === 'pending' && $candidate->matched_device_id) {
                $this->confirmCandidate($candidate);
                $count++;
            }
        }
        return $count;
    }
}
