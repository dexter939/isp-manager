<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Enums\DunningAction;

class DunningStep extends Model
{
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'customer_id',
        'contract_id',
        'step',
        'action',
        'status',
        'scheduled_at',
        'executed_at',
        'result_log',
    ];

    protected $casts = [
        'action'       => DunningAction::class,
        'step'         => 'integer',
        'scheduled_at' => 'datetime',
        'executed_at'  => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDue(): bool
    {
        return $this->isPending() && $this->scheduled_at->isPast();
    }

    public function markExecuted(string $log): void
    {
        $this->update([
            'status'      => 'executed',
            'executed_at' => now(),
            'result_log'  => $log,
        ]);
    }

    public function markFailed(string $log): void
    {
        $this->update([
            'status'      => 'failed',
            'executed_at' => now(),
            'result_log'  => $log,
        ]);
    }

    public function scopePendingDue($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }
}
