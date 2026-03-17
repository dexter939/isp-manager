<?php

declare(strict_types=1);

namespace Modules\Contracts\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Enums\CarrierEnum;

class ServicePlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'carrier', 'technology',
        'price_monthly', 'activation_fee', 'modem_fee',
        'carrier_product_code', 'bandwidth_dl', 'bandwidth_ul',
        'sla_type', 'mtr_hours', 'is_active', 'is_public',
        'min_contract_months', 'description',
    ];

    protected $casts = [
        'carrier'              => CarrierEnum::class,
        'price_monthly'        => 'decimal:2',
        'activation_fee'       => 'decimal:2',
        'modem_fee'            => 'decimal:2',
        'bandwidth_dl'         => 'integer',
        'bandwidth_ul'         => 'integer',
        'min_contract_months'  => 'integer',
        'is_active'            => 'boolean',
        'is_public'            => 'boolean',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    // ---- Accessors brick/money ----

    /** Prezzo mensile come oggetto Money (EUR) — MAI float per i soldi */
    public function priceMoneyMonthly(): Money
    {
        return Money::of((string) $this->price_monthly, 'EUR');
    }

    public function activationFeeMoney(): Money
    {
        return Money::of((string) $this->activation_fee, 'EUR');
    }

    // ---- Scopes ----

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query): mixed
    {
        return $query->where('is_public', true);
    }

    public function scopeForCarrier($query, string|CarrierEnum $carrier): mixed
    {
        $value = $carrier instanceof CarrierEnum ? $carrier->value : $carrier;
        return $query->where('carrier', $value);
    }

    public function scopeForTechnology($query, string $technology): mixed
    {
        return $query->where('technology', strtoupper($technology));
    }
}
