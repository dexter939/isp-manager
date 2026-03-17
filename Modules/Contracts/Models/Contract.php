<?php

declare(strict_types=1);

namespace Modules\Contracts\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Enums\BillingCycle;
use Modules\Contracts\Enums\CarrierEnum;
use Modules\Contracts\Enums\ContractStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'customer_id', 'service_plan_id',
        'indirizzo_installazione', 'codice_ui', 'id_building', 'carrier',
        'billing_cycle', 'billing_day', 'monthly_price', 'activation_fee', 'modem_fee',
        'activation_date', 'termination_date', 'min_end_date',
        'status', 'signed_at', 'signed_ip', 'pdf_path', 'pdf_hash_sha256',
        'agent_id', 'notes',
    ];

    protected $casts = [
        'indirizzo_installazione' => 'array',
        'carrier'                 => CarrierEnum::class,
        'billing_cycle'           => BillingCycle::class,
        'status'                  => ContractStatus::class,
        'monthly_price'           => 'decimal:2',
        'activation_fee'          => 'decimal:2',
        'modem_fee'               => 'decimal:2',
        'billing_day'             => 'integer',
        'activation_date'         => 'date',
        'termination_date'        => 'date',
        'min_end_date'            => 'date',
        'signed_at'               => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'billing_day', 'service_plan_id', 'signed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('contracts');
    }

    // ---- Relazioni ----

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function servicePlan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'agent_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function latestSignature(): HasOne
    {
        return $this->hasOne(ContractSignature::class)->latestOfMany();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    // ---- Accessors ----

    public function monthlyPriceMoney(): Money
    {
        return Money::of((string) $this->monthly_price, 'EUR');
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === ContractStatus::Active;
    }

    // ---- Scopes ----

    public function scopeActive($query): mixed
    {
        return $query->where('status', ContractStatus::Active->value);
    }

    public function scopeBillableOnDay($query, int $day): mixed
    {
        return $query->active()->where('billing_day', $day);
    }

    public function scopeForCarrier($query, string|CarrierEnum $carrier): mixed
    {
        $value = $carrier instanceof CarrierEnum ? $carrier->value : $carrier;
        return $query->where('carrier', $value);
    }
}
