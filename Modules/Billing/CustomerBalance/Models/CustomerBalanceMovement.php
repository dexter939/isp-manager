<?php
namespace Modules\Billing\CustomerBalance\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Brick\Money\Money;
class CustomerBalanceMovement extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'customer_balance_movements';
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function getAmountAttribute(): Money {
        return Money::ofMinor($this->amount_amount, $this->amount_currency);
    }
}
