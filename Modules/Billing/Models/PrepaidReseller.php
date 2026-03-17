<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Brick\Money\Money;
use Brick\Money\RoundingMode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contracts\Models\Customer;

class PrepaidReseller extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'wallet_id',
        'commission_type',
        'commission_value_amount',
        'commission_currency',
        'is_active',
    ];

    protected $casts = [
        'commission_type'         => 'string',
        'is_active'               => 'boolean',
        'commission_value_amount' => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PrepaidWallet::class, 'wallet_id');
    }

    // ── Business Logic ───────────────────────────────────────────────────────

    public function calculateCommission(Money $amount): Money
    {
        if ($this->commission_type === 'fixed') {
            $currency = $this->commission_currency ?? 'EUR';
            return Money::ofMinor($this->commission_value_amount, $currency);
        }

        // Percentage: commission_value_amount is in basis points (e.g. 1000 = 10%)
        return $amount->multipliedBy($this->commission_value_amount / 10000, RoundingMode::HALF_UP);
    }
}
