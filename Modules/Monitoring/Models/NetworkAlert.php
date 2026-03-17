<?php

declare(strict_types=1);

namespace Modules\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Network\Models\CpeDevice;

class NetworkAlert extends Model
{
    protected $fillable = [
        'tenant_id',
        'cpe_device_id',
        'customer_id',
        'contract_id',
        'source',
        'severity',
        'type',
        'message',
        'details',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'details'          => 'array',
        'acknowledged_at'  => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function acknowledge(int $userId): void
    {
        $this->update([
            'status'           => 'acknowledged',
            'acknowledged_by'  => $userId,
            'acknowledged_at'  => now(),
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
