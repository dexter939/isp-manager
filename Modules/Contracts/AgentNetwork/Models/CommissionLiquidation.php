<?php

namespace Modules\Contracts\AgentNetwork\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionLiquidation extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid', 'agent_id', 'period_month', 'total_amount_cents',
        'status', 'approved_at', 'approved_by', 'paid_at', 'iban',
    ];

    protected $casts = [
        'period_month' => 'date',
        'approved_at'  => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function getTotalAmountAttribute(): Money
    {
        return Money::ofMinor($this->total_amount_cents, 'EUR');
    }

    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
    public function entries(): HasMany { return $this->hasMany(CommissionEntry::class, 'liquidation_id'); }

    public function uniqueIds(): array { return ['uuid']; }
}
