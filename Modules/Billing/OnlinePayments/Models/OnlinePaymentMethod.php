<?php

namespace Modules\Billing\OnlinePayments\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlinePaymentMethod extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid', 'customer_id', 'gateway', 'external_customer_id', 'external_method_id',
        'card_brand', 'card_last4', 'card_expiry', 'is_default', 'active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'active'     => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(OnlinePaymentTransaction::class, 'payment_method_id');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
