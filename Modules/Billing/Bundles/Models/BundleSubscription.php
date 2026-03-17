<?php
namespace Modules\Billing\Bundles\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Brick\Money\Money;
class BundleSubscription extends Model {
    use HasUuids;
    protected $table = 'bundle_subscriptions';
    protected $guarded = ['id'];
    protected $casts = ['start_date'=>'date','end_date'=>'date'];
    public function uniqueIds(): array { return ['id']; }
    public function plan() { return $this->belongsTo(BundlePlan::class, 'bundle_plan_id'); }
    public function getEffectivePriceAttribute(): Money {
        $cents = $this->custom_price_amount ?? $this->plan->price_amount;
        return Money::ofMinor($cents, $this->plan->price_currency);
    }
}
