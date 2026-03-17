<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Monitoring\Models\Tr069Parameter;

class CpeDevice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'bts_station_id',
        'mac_address',
        'serial_number',
        'model',
        'manufacturer',
        'firmware_version',
        'type',
        'technology',
        'tr069_id',
        'tr069_inform_ip',
        'tr069_last_inform',
        'wan_ip',
        'lan_ip',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'tr069_last_inform' => 'datetime',
        'last_seen_at'      => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function btsStation(): BelongsTo
    {
        return $this->belongsTo(BtsStation::class);
    }

    public function tr069Parameters(): HasMany
    {
        return $this->hasMany(Tr069Parameter::class);
    }

    public function isOnline(): bool
    {
        if (!$this->last_seen_at) {
            return false;
        }
        return $this->last_seen_at->gt(now()->subMinutes(15));
    }

    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>', now()->subMinutes(15));
    }

    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }
}
