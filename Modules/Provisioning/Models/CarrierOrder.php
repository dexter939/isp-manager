<?php

declare(strict_types=1);

namespace Modules\Provisioning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Models\Contract;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Enums\OrderType;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CarrierOrder extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'contract_id', 'carrier', 'order_type',
        'codice_ordine_olo', 'codice_ordine_of',
        'state', 'scheduled_date', 'cvlan', 'gpon_attestazione',
        'id_apparato_consegnato', 'vlan_pool_id',
        'payload_sent', 'payload_received',
        'last_error', 'retry_count', 'next_retry_at',
        'sent_by', 'sent_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'state'          => OrderState::class,
        'order_type'     => OrderType::class,
        'scheduled_date' => 'datetime',
        'sent_at'        => 'datetime',
        'completed_at'   => 'datetime',
        'next_retry_at'  => 'datetime',
        'retry_count'    => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state', 'codice_ordine_of', 'scheduled_date', 'cvlan', 'last_error'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('carrier_orders');
    }

    // ---- Relazioni ----

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function vlanPool(): BelongsTo
    {
        return $this->belongsTo(VlanPool::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CarrierEventLog::class);
    }

    // ---- Helpers ----

    public function isRetryable(): bool
    {
        return $this->state->isRetryable()
            && $this->retry_count < 3;
    }

    public function scheduleRetry(): void
    {
        $delays = [0, 5 * 60, 30 * 60]; // secondi: immediato, 5 min, 30 min
        $delay  = $delays[$this->retry_count] ?? null;

        if ($delay === null) {
            $this->update(['state' => OrderState::RetryFailed->value]);
            return;
        }

        $this->update([
            'retry_count'  => $this->retry_count + 1,
            'next_retry_at'=> now()->addSeconds($delay),
        ]);
    }

    // ---- Scopes ----

    public function scopePendingRetry($query): mixed
    {
        return $query->whereIn('state', [OrderState::Ko->value, OrderState::RetryFailed->value])
            ->where('retry_count', '<', 3)
            ->where(fn($q) => $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now()));
    }

    public function scopeActive($query): mixed
    {
        return $query->whereNotIn('state', [
            OrderState::Completed->value,
            OrderState::Cancelled->value,
            OrderState::RetryFailed->value,
        ]);
    }
}
