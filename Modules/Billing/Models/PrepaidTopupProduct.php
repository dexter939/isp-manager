<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PrepaidTopupProduct extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'amount_amount',
        'amount_currency',
        'bonus_amount',
        'validity_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'amount_amount' => 'integer',
        'bonus_amount'  => 'integer',
        'sort_order'    => 'integer',
        'validity_days' => 'integer',
    ];

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getAmountAttribute(): Money
    {
        return Money::ofMinor($this->amount_amount, $this->amount_currency);
    }

    public function getBonusAttribute(): Money
    {
        return Money::ofMinor($this->bonus_amount, $this->amount_currency);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
