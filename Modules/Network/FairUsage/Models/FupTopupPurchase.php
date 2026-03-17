<?php
namespace Modules\Network\FairUsage\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Brick\Money\Money;
class FupTopupPurchase extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'fup_topup_purchases';
    protected $guarded = ['id'];
    protected $casts = ['created_at'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function getPriceAttribute(): Money { return Money::ofMinor($this->price_amount, $this->price_currency); }
}
