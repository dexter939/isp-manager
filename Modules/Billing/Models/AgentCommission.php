<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Models\Contract;

class AgentCommission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'contract_id',
        'invoice_id',
        'type',
        'base_amount',
        'rate',
        'amount',
        'currency',
        'status',
        'accrued_on',
        'paid_on',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'rate'        => 'decimal:4',
        'amount'      => 'decimal:2',
        'accrued_on'  => 'date',
        'paid_on'     => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeAccrued($query)
    {
        return $query->where('status', 'accrued');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }
}
