<?php
namespace Modules\Infrastructure\Topology\Services;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Infrastructure\Topology\Models\TopologyLink;
class TopologyService {
    public function getGraph(?string $siteId = null): array {
        $query = TopologyLink::query();
        if ($siteId) $query->where('network_site_id', $siteId);
        $links = $query->get();
        $deviceIds = $links->flatMap(fn($l) => [$l->source_device_id, $l->target_device_id])->unique();
        $devices = DB::table('hardware_devices')->whereIn('id', $deviceIds)->get(['id','name','type','ip_address','status'])->keyBy('id');
        $nodes = $deviceIds->map(fn($id) => ['id'=>$id,'label'=>$devices[$id]?->name ?? $id,'type'=>$devices[$id]?->type ?? 'unknown','status'=>$devices[$id]?->status ?? 'unknown','ip'=>$devices[$id]?->ip_address ?? null])->values()->toArray();
        $edges = $links->map(fn($l) => ['id'=>$l->id,'from'=>$l->source_device_id,'to'=>$l->target_device_id,'link_type'=>$l->link_type,'status'=>$l->status,'bandwidth_mbps'=>$l->bandwidth_mbps,'source_interface'=>$l->source_interface,'target_interface'=>$l->target_interface])->toArray();
        return ['nodes'=>$nodes,'edges'=>$edges];
    }
    public function getImpactedDevices(string $failedDeviceId): Collection {
        // BFS/DFS traversal: find all devices "downstream" of the failed device
        // downstream = devices that have the failed device as their source
        $visited   = collect([$failedDeviceId]);
        $queue     = [$failedDeviceId];
        $impacted  = collect();
        while (!empty($queue)) {
            $current   = array_shift($queue);
            $children  = TopologyLink::where('source_device_id', $current)->pluck('target_device_id');
            foreach ($children as $childId) {
                if (!$visited->contains($childId)) {
                    $visited->push($childId);
                    $impacted->push($childId);
                    $queue[] = $childId;
                }
            }
        }
        return $impacted;
    }
    public function updateLinkStatus(TopologyLink $link, string $status): void {
        $link->update(['status'=>$status,'last_status_change'=>now()]);
        event(new \Illuminate\Foundation\Events\LocalServerRunning()); // placeholder — real app dispatches TopologyLinkStatusChanged event
    }
}
