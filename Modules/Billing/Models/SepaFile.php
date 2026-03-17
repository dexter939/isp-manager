<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SepaFile extends Model
{
    protected $fillable = [
        'tenant_id',
        'message_id',
        'type',
        'filename',
        'transaction_count',
        'control_sum',
        'settlement_date',
        'status',
        'storage_path',
        'submitted_at',
        'bank_acknowledged_at',
        'bank_response_raw',
    ];

    protected $casts = [
        'control_sum'          => 'decimal:2',
        'settlement_date'      => 'date',
        'submitted_at'         => 'datetime',
        'bank_acknowledged_at' => 'datetime',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'sepa_file_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'generated');
    }
}
