<?php

declare(strict_types=1);

namespace Modules\Provisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contracts\Models\Contract;

class VlanPool extends Model
{
    protected $table = 'vlan_pool';

    protected $fillable = [
        'tenant_id', 'carrier', 'vlan_type', 'vlan_id',
        'status', 'contract_id', 'assigned_at', 'notes',
    ];

    protected $casts = [
        'vlan_id'     => 'integer',
        'assigned_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function scopeFree($query): mixed
    {
        return $query->where('status', 'free');
    }

    public function scopeForCarrier($query, string $carrier): mixed
    {
        return $query->where('carrier', $carrier);
    }
}
