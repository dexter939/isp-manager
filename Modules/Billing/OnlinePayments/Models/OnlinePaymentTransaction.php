<?php

namespace Modules\Billing\OnlinePayments\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePaymentTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid', 'payment_method_id', 'invoice_id', 'gateway',
        'external_transaction_id', 'amount_cents', 'currency',
        'status', 'is_recurring', 'metadata',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'metadata'     => 'array',
        'amount_cents' => 'integer',
    ];

    public function getAmountAttribute(): Money
    {
        return Money::ofMinor($this->amount_cents, $this->currency ?? 'EUR');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(OnlinePaymentMethod::class, 'payment_method_id');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
