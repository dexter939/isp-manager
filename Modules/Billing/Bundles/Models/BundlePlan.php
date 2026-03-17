<?php
namespace Modules\Billing\Bundles\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Brick\Money\Money;
use Modules\Billing\Bundles\Database\Factories\BundlePlanFactory;
use Modules\Billing\Bundles\Enums\BillingPeriod;
class BundlePlan extends Model {
    use HasUuids, HasFactory;
    protected static function newFactory(): BundlePlanFactory { return BundlePlanFactory::new(); }
    protected $table = 'bundle_plans';
    protected $guarded = ['id'];
    protected $casts = ['is_active'=>'boolean','billing_period'=>BillingPeriod::class];
    public function uniqueIds(): array { return ['id']; }
    public function items() { return $this->hasMany(BundlePlanItem::class, 'bundle_plan_id')->orderBy('sort_order'); }
    public function subscriptions() { return $this->hasMany(BundleSubscription::class, 'bundle_plan_id'); }
    public function getPriceAttribute(): Money { return Money::ofMinor($this->price_amount, $this->price_currency); }
}
