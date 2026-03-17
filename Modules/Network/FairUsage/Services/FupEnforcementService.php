<?php
namespace Modules\Network\FairUsage\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class FupEnforcementService {
    public function applyFup(object $pppoeAccount, object $service): void {
        if (!$service->fup_service_id) { Log::warning("FUP: no fup_service_id configured for service {$service->id}"); return; }
        if (config('app.carrier_mock', false)) { Log::info("MOCK FUP: applying throttle to account {$pppoeAccount->username}"); return; }
        // Change RADIUS profile to FUP service profile
        try {
            $fupService = DB::table('services')->find($service->fup_service_id);
            if ($fupService) {
                DB::table('radreply')->updateOrInsert(['username'=>$pppoeAccount->username,'attribute'=>'Mikrotik-Rate-Limit'],['value'=>$fupService->radius_rate_limit ?? '1M/1M','op'=>':=']);
                // Send CoA to NAS
                $this->sendCoA($pppoeAccount->username);
            }
        } catch (\Throwable $e) { Log::error("FupEnforcementService::applyFup failed: {$e->getMessage()}"); }
    }
    public function removeFup(object $pppoeAccount): void {
        if (config('app.carrier_mock', false)) { Log::info("MOCK FUP: removing throttle from account {$pppoeAccount->username}"); return; }
        try {
            $account = DB::table('pppoe_accounts')->find($pppoeAccount->id ?? $pppoeAccount);
            $service = $account ? DB::table('services')->find($account->service_id) : null;
            if ($service) {
                DB::table('radreply')->updateOrInsert(['username'=>$pppoeAccount->username,'attribute'=>'Mikrotik-Rate-Limit'],['value'=>$service->radius_rate_limit ?? '100M/100M','op'=>':=']);
                $this->sendCoA($pppoeAccount->username);
            }
        } catch (\Throwable $e) { Log::error("FupEnforcementService::removeFup failed: {$e->getMessage()}"); }
    }
    private function sendCoA(string $username): void {
        // Dispatch CoA via existing RadiusService/CoaService
        try { app(\Modules\Network\Services\CoaService::class)->sendForUser($username); } catch (\Throwable $e) { Log::warning("CoA send failed for {$username}: {$e->getMessage()}"); }
    }
}
