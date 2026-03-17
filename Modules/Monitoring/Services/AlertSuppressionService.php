<?php
namespace Modules\Monitoring\Services;
use Illuminate\Support\Facades\DB;
class AlertSuppressionService {
    public function shouldSuppress(object $device): ?object {
        $current = $device;
        $visited = [$current->id];
        while ($current->parent_device_id ?? null) {
            if (in_array($current->parent_device_id, $visited)) break; // prevent infinite loop
            $parent = DB::table('hardware_devices')->find($current->parent_device_id);
            if (!$parent) break;
            if (in_array($parent->status ?? '', ['offline','down','unreachable'])) return $parent;
            $visited[] = $parent->id;
            $current   = $parent;
        }
        return null;
    }
    public function suppressDescendants(object $parentDevice): int {
        $impactedIds = $this->getDescendantIds($parentDevice->id);
        if (empty($impactedIds)) return 0;
        return DB::table('monitoring_alerts')->whereIn('device_id', $impactedIds)->where('suppressed', false)->update(['suppressed'=>true,'suppressed_by_device_id'=>$parentDevice->id,'suppressed_reason'=>"Parent device {$parentDevice->name} is down"]);
    }
    public function restoreDescendants(object $parentDevice): int {
        $impactedIds = $this->getDescendantIds($parentDevice->id);
        if (empty($impactedIds)) return 0;
        return DB::table('monitoring_alerts')->whereIn('device_id', $impactedIds)->where('suppressed_by_device_id', $parentDevice->id)->update(['suppressed'=>false,'suppressed_by_device_id'=>null,'suppressed_reason'=>null]);
    }
    private function getDescendantIds(string $deviceId): array {
        // Use topology links if available, fall back to parent_device_id chain
        $descendants = [];
        $queue       = [$deviceId];
        $visited     = [$deviceId];
        while (!empty($queue)) {
            $current  = array_shift($queue);
            // Find devices that have $current as parent
            $children = DB::table('hardware_devices')->where('parent_device_id', $current)->pluck('id');
            // Also find devices connected downstream in topology
            $topologyChildren = DB::table('topology_links')->where('source_device_id', $current)->pluck('target_device_id');
            $allChildren = $children->merge($topologyChildren)->unique();
            foreach ($allChildren as $childId) {
                if (!in_array($childId, $visited)) {
                    $visited[]     = $childId;
                    $descendants[] = $childId;
                    $queue[]       = $childId;
                }
            }
        }
        return $descendants;
    }
}
