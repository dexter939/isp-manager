<?php
namespace Modules\Infrastructure\NetworkSites\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Infrastructure\NetworkSites\Models\NetworkSite;
use Modules\Infrastructure\NetworkSites\Models\NetworkSiteHardware;
use Modules\Infrastructure\NetworkSites\Models\NetworkSiteCustomerService;
class NetworkSiteService {
    public function getWithStats(NetworkSite $site): array {
        $hardwareIds = NetworkSiteHardware::where('network_site_id', $site->id)->pluck('hardware_id');
        $hardwareOnline  = DB::table('hardware_devices')->whereIn('id', $hardwareIds)->where('status','online')->count();
        $hardwareOffline = DB::table('hardware_devices')->whereIn('id', $hardwareIds)->whereIn('status',['offline','unreachable'])->count();
        $contractIds   = NetworkSiteCustomerService::where('network_site_id', $site->id)->pluck('contract_id');
        $clientsActive    = DB::table('contracts')->whereIn('id', $contractIds)->where('status','active')->count();
        $clientsSuspended = DB::table('contracts')->whereIn('id', $contractIds)->where('status','suspended')->count();
        $recentAlerts = DB::table('monitoring_alerts')->whereIn('device_id', $hardwareIds)->where('created_at','>=', now()->subHours(24))->orderByDesc('created_at')->limit(5)->get();
        $overallStatus = $hardwareOffline > 0 ? 'degraded' : 'ok';
        if ($hardwareOffline > 0 && $hardwareOnline === 0) $overallStatus = 'down';
        return ['site'=>$site,'hardware_online'=>$hardwareOnline,'hardware_offline'=>$hardwareOffline,'hardware_total'=>$hardwareIds->count(),'clients_active'=>$clientsActive,'clients_suspended'=>$clientsSuspended,'clients_total'=>$contractIds->count(),'recent_alerts'=>$recentAlerts,'overall_status'=>$overallStatus];
    }
    public function linkHardware(NetworkSite $site, string $hardwareId, bool $isAccessDevice = false): void {
        DB::table('network_site_hardware')->updateOrInsert(['network_site_id'=>$site->id,'hardware_id'=>$hardwareId],['id'=>Str::uuid(),'is_access_device'=>$isAccessDevice,'linked_at'=>now()]);
    }
    public function linkCustomerService(NetworkSite $site, string $hardwareId, string $contractId): void {
        DB::table('network_site_customer_services')->updateOrInsert(['network_site_id'=>$site->id,'contract_id'=>$contractId],['id'=>Str::uuid(),'hardware_id'=>$hardwareId,'linked_at'=>now()]);
    }
    public function bulkLinkCustomerServices(NetworkSite $site, string $hardwareId, array $contractIds): int {
        $count = 0;
        foreach ($contractIds as $contractId) {
            $this->linkCustomerService($site, $hardwareId, $contractId);
            $count++;
        }
        return $count;
    }
}
