<?php
namespace Modules\Billing\Bundles\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Brick\Money\Money;
use Modules\Billing\Bundles\Database\Factories\BundlePlanItemFactory;
class BundlePlanItem extends Model {
    use HasUuids, HasFactory;
    protected static function newFactory(): BundlePlanItemFactory { return BundlePlanItemFactory::new(); }
    public $timestamps = false;
    protected $table = 'bundle_plan_items';
    protected $guarded = ['id'];
    public function uniqueIds(): array { return ['id']; }
    public function plan() { return $this->belongsTo(BundlePlan::class, 'bundle_plan_id'); }
    public function getListPriceAttribute(): Money { return Money::ofMinor($this->list_price_amount, 'EUR'); }
}
