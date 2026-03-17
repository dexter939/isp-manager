<?php
namespace Modules\Billing\Cdr\Models;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CdrRate extends Model {
    protected $fillable = [
        'tariff_plan_id','prefix','destination_name','category',
        'rate_per_minute_cents','connection_fee_cents','billing_interval_seconds','active','valid_from','valid_to',
    ];
    protected $casts = ['active'=>'boolean','valid_from'=>'date','valid_to'=>'date'];
    public function getRatePerMinuteAttribute(): Money { return Money::ofMinor($this->rate_per_minute_cents,'EUR'); }
    public function plan(): BelongsTo { return $this->belongsTo(CdrTariffPlan::class,'tariff_plan_id'); }
}
