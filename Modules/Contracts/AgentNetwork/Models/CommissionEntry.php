<?php

namespace Modules\Contracts\AgentNetwork\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid', 'agent_id', 'contract_id', 'invoice_id', 'rule_id',
        'amount_cents', 'status', 'period_month', 'liquidation_id',
    ];

    protected $casts = ['period_month' => 'date', 'amount_cents' => 'integer'];

    public function getAmountAttribute(): Money
    {
        return Money::ofMinor($this->amount_cents, 'EUR');
    }

    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
    public function liquidation(): BelongsTo { return $this->belongsTo(CommissionLiquidation::class); }

    public function uniqueIds(): array { return ['uuid']; }
}
