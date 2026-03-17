<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Billing\Enums\PrepaidWalletStatus;
use Modules\Contracts\Models\Customer;

class PrepaidWallet extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'balance_amount',
        'balance_currency',
        'status',
        'low_balance_threshold_amount',
        'auto_suspend_on_zero',
    ];

    protected $casts = [
        'status'              => PrepaidWalletStatus::class,
        'auto_suspend_on_zero' => 'boolean',
        'balance_amount'      => 'integer',
        'low_balance_threshold_amount' => 'integer',
    ];

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getBalanceAttribute(): Money
    {
        return Money::ofMinor($this->balance_amount, $this->balance_currency);
    }

    public function getLowBalanceThresholdAttribute(): Money
    {
        return Money::ofMinor($this->low_balance_threshold_amount, $this->balance_currency);
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PrepaidTransaction::class, 'wallet_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PrepaidTopupOrder::class, 'wallet_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', PrepaidWalletStatus::Active->value);
    }

    public function scopeExhausted($query)
    {
        return $query->where('status', PrepaidWalletStatus::Exhausted->value);
    }

    public function scopeLowBalance($query)
    {
        return $query->where('balance_amount', '<=', $query->getModel()->getTable() . '.low_balance_threshold_amount')
                     ->where('balance_amount', '>', 0);
    }
}
