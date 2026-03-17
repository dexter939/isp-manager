<?php
namespace Modules\Network\FairUsage\Services;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\Network\FairUsage\Models\FupTopupProduct;
use Modules\Network\FairUsage\Models\FupTopupPurchase;
class TopupService {
    public function __construct(private FupEnforcementService $fupService) {}
    public function purchase(object $pppoeAccount, FupTopupProduct $product, string $paymentMethod): FupTopupPurchase {
        return DB::transaction(function () use ($pppoeAccount, $product, $paymentMethod) {
            $year  = (int)date('Y');
            $month = (int)date('n');
            $purchase = FupTopupPurchase::create(['pppoe_account_id'=>$pppoeAccount->id,'product_id'=>$product->id,'period_year'=>$year,'period_month'=>$month,'gb_added'=>$product->gb_amount,'price_amount'=>$product->price_amount,'price_currency'=>$product->price_currency,'payment_method'=>$paymentMethod]);
            // Add GB to usage counter
            DB::table('customer_traffic_usage')->updateOrInsert(['pppoe_account_id'=>$pppoeAccount->id,'period_year'=>$year,'period_month'=>$month],['topup_gb_added'=>DB::raw("topup_gb_added + {$product->gb_amount}"),'last_updated'=>now()]);
            // Remove FUP if user now has enough traffic
            $usage = DB::table('customer_traffic_usage')->where('pppoe_account_id',$pppoeAccount->id)->where('period_year',$year)->where('period_month',$month)->first();
            if ($usage && $usage->fup_triggered) {
                $totalBytes = (($usage->cap_gb ?? 0) + ($usage->topup_gb_added ?? 0)) * 1073741824;
                if ($usage->bytes_total < $totalBytes) {
                    $this->fupService->removeFup($pppoeAccount);
                    DB::table('customer_traffic_usage')->where('pppoe_account_id',$pppoeAccount->id)->where('period_year',$year)->where('period_month',$month)->update(['fup_triggered'=>false,'fup_triggered_at'=>null]);
                }
            }
            return $purchase;
        });
    }
}
