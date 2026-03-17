<?php

namespace Modules\Contracts\AgentNetwork\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentContractAssignment extends Model
{
    protected $table = 'agent_contract_assignments';
    protected $fillable = ['agent_id', 'contract_id', 'assigned_at'];
    protected $casts = ['assigned_at' => 'datetime'];

    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
}
