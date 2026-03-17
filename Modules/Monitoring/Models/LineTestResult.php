<?php

declare(strict_types=1);

namespace Modules\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contracts\Models\Contract;

class LineTestResult extends Model
{
    protected $fillable = [
        'tenant_id',
        'contract_id',
        'customer_id',
        'carrier',
        'resource_id',
        'result',
        'error_code',
        'ont_state',
        'attenuation_dbm',
        'optical_distance_m',
        'ont_lan_status',
        'test_instance_id',
        'is_retryable',
        'triggered_ticket',
        'raw_response',
        'initiated_by',
    ];

    protected $casts = [
        'attenuation_dbm'    => 'decimal:2',
        'optical_distance_m' => 'decimal:1',
        'is_retryable'       => 'boolean',
        'triggered_ticket'   => 'boolean',
        'raw_response'       => 'array',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isOk(): bool
    {
        return $this->result === 'OK';
    }

    public function isOntOnline(): bool
    {
        return $this->isOk() && $this->ont_state === 'UP';
    }

    public function scopeKo($query)
    {
        return $query->where('result', 'KO');
    }

    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeLatestPerContract($query)
    {
        return $query->orderByDesc('created_at');
    }
}
