<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Enums\PrepaidOrderStatus;
use Modules\Billing\Enums\PrepaidPaymentMethod;

class PrepaidTopupOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'product_id',
        'reseller_id',
        'amount_amount',
        'amount_currency',
        'commission_amount',
        'payment_method',
        'payment_reference',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'status'            => PrepaidOrderStatus::class,
        'payment_method'    => PrepaidPaymentMethod::class,
        'completed_at'      => 'datetime',
        'amount_amount'     => 'integer',
        'commission_amount' => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PrepaidWallet::class, 'wallet_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PrepaidTopupProduct::class, 'product_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(PrepaidReseller::class, 'reseller_id');
    }
}
