<?php

namespace Modules\Contracts\AgentNetwork\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    protected $fillable = [
        'agent_id', 'offer_type', 'contract_type', 'rate_type',
        'rate_value_cents', 'rate_percentage', 'priority', 'active',
        'valid_from', 'valid_to',
    ];

    protected $casts = [
        'rate_percentage' => 'decimal:4',
        'active'          => 'boolean',
        'valid_from'      => 'date',
        'valid_to'        => 'date',
    ];

    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
}
