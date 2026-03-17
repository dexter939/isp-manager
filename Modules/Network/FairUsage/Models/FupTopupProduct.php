<?php
namespace Modules\Network\FairUsage\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Brick\Money\Money;
class FupTopupProduct extends Model {
    use HasUuids;
    protected $table = 'fup_topup_products';
    protected $guarded = ['id'];
    protected $casts = ['is_active'=>'boolean'];
    public function uniqueIds(): array { return ['id']; }
    public function getPriceAttribute(): Money { return Money::ofMinor($this->price_amount, $this->price_currency); }
}
