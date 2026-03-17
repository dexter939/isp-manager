<?php
namespace Modules\Network\FairUsage\Services;
use Illuminate\Support\Facades\DB;
class TrafficAccountingService {
    public function __construct(private FupEnforcementService $fupService) {}
    public function updateUsage(string $pppoeAccountId, int $bytesIn, int $bytesOut): void {
        $year  = (int)date('Y');
        $month = (int)date('n');
        DB::table('customer_traffic_usage')->updateOrInsert(['pppoe_account_id'=>$pppoeAccountId,'period_year'=>$year,'period_month'=>$month],['bytes_download'=>DB::raw("bytes_download + {$bytesIn}"),'bytes_upload'=>DB::raw("bytes_upload + {$bytesOut}"),'bytes_total'=>DB::raw("bytes_total + {$bytesIn} + {$bytesOut}"),'last_updated'=>now()]);
        $usage = DB::table('customer_traffic_usage')->where('pppoe_account_id',$pppoeAccountId)->where('period_year',$year)->where('period_month',$month)->first();
        if (!$usage || $usage->fup_triggered || !$usage->cap_gb) return;
        $account = DB::table('pppoe_accounts')->find($pppoeAccountId);
        if (!$account) return;
        $service = DB::table('services')->find($account->service_id);
        if (!$service || !$service->cap_enabled) return;
        $capBytes   = ($usage->cap_gb + ($usage->topup_gb_added ?? 0)) * 1073741824;
        $threshold  = ($service->fup_threshold_percent ?? 100) / 100;
        if ($usage->bytes_total >= $capBytes * $threshold) {
            $this->fupService->applyFup($account, $service);
            DB::table('customer_traffic_usage')->where('pppoe_account_id',$pppoeAccountId)->where('period_year',$year)->where('period_month',$month)->update(['fup_triggered'=>true,'fup_triggered_at'=>now()]);
        }
    }
    public function resetMonthlyCounters(): int {
        $accounts = DB::table('pppoe_accounts as pa')
            ->join('services as s', 'pa.service_id', '=', 's.id')
            ->where('s.cap_enabled', true)
            ->select('pa.id as pppoe_id', 'pa.service_id', 's.cap_gb')
            ->get();
        $year  = (int)date('Y');
        $month = (int)date('n');
        $count = 0;
        foreach ($accounts as $account) {
            // Remove FUP if it was triggered
            $wasThrottled = DB::table('customer_traffic_usage')
                ->where('pppoe_account_id', $account->pppoe_id)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->value('fup_triggered');
            if ($wasThrottled) {
                $pppoeAccount = DB::table('pppoe_accounts')->find($account->pppoe_id);
                if ($pppoeAccount) $this->fupService->removeFup($pppoeAccount);
            }
            DB::table('customer_traffic_usage')->updateOrInsert(['pppoe_account_id'=>$account->pppoe_id,'period_year'=>$year,'period_month'=>$month],['bytes_download'=>0,'bytes_upload'=>0,'bytes_total'=>0,'cap_gb'=>$account->cap_gb,'fup_triggered'=>false,'fup_triggered_at'=>null,'topup_gb_added'=>0,'last_updated'=>now()]);
            $count++;
        }
        return $count;
    }
}
