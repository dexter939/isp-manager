<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Billing\DunningManager\Enums\DunningStatus;
use Modules\Billing\Models\Invoice;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;

class DunningCase extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid',
        'invoice_id',
        'customer_id',
        'contract_id',
        'policy_id',
        'status',
        'opened_at',
        'resolved_at',
        'current_step_index',
        'next_action_at',
        'total_penalty_cents',
    ];

    protected $casts = [
        'status'              => DunningStatus::class,
        'opened_at'           => 'datetime',
        'resolved_at'         => 'datetime',
        'next_action_at'      => 'datetime',
        'current_step_index'  => 'integer',
        'total_penalty_cents' => 'integer',
    ];

    /**
     * Returns the total penalty as a Money object (EUR).
     */
    public function getTotalPenaltyAttribute(): Money
    {
        return Money::ofMinor($this->total_penalty_cents, 'EUR');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(DunningPolicy::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DunningStep::class, 'case_id');
    }

    /**
     * Scope: only open cases.
     */
    public function scopeOpen(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', DunningStatus::Open->value);
    }

    /**
     * Scope: cases with next action due now or in the past.
     */
    public function scopeDue(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('next_action_at', '<=', now());
    }
}
