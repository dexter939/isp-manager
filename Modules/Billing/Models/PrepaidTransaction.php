<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Enums\PrepaidPaymentMethod;
use Modules\Billing\Enums\PrepaidTransactionDirection;
use Modules\Billing\Enums\PrepaidTransactionType;

class PrepaidTransaction extends Model
{
    use HasUuids;

    /** @var null */
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'type',
        'amount_amount',
        'amount_currency',
        'direction',
        'balance_before_amount',
        'balance_after_amount',
        'description',
        'reference_id',
        'payment_method',
    ];

    protected $casts = [
        'type'                  => PrepaidTransactionType::class,
        'direction'             => PrepaidTransactionDirection::class,
        'payment_method'        => PrepaidPaymentMethod::class,
        'amount_amount'         => 'integer',
        'balance_before_amount' => 'integer',
        'balance_after_amount'  => 'integer',
    ];

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getAmountAttribute(): Money
    {
        return Money::ofMinor($this->amount_amount, $this->amount_currency);
    }

    public function getBalanceBeforeAttribute(): Money
    {
        return Money::ofMinor($this->balance_before_amount, $this->amount_currency);
    }

    public function getBalanceAfterAttribute(): Money
    {
        return Money::ofMinor($this->balance_after_amount, $this->amount_currency);
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PrepaidWallet::class, 'wallet_id');
    }
}
