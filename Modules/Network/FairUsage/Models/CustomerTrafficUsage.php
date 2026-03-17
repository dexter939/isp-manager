<?php
namespace Modules\Network\FairUsage\Models;
use Illuminate\Database\Eloquent\Model;
use Brick\Money\Money;
class CustomerTrafficUsage extends Model {
    public $incrementing = false;
    protected $primaryKey = null;
    protected $table = 'customer_traffic_usage';
    protected $guarded = [];
    protected $casts = ['fup_triggered'=>'boolean','fup_triggered_at'=>'datetime','last_updated'=>'datetime'];
    public function getUsagePercentAttribute(): float {
        if (!$this->cap_gb) return 0.0;
        $totalBytes = ($this->cap_gb + ($this->topup_gb_added ?? 0)) * 1073741824;
        return round(($this->bytes_total / $totalBytes) * 100, 2);
    }
}
