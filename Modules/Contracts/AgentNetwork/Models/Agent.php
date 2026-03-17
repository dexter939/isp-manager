<?php

namespace Modules\Contracts\AgentNetwork\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid', 'user_id', 'parent_agent_id', 'code', 'business_name',
        'piva', 'codice_fiscale', 'iban', 'status', 'commission_rate',
    ];

    protected $casts = ['commission_rate' => 'decimal:4'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function parent(): BelongsTo { return $this->belongsTo(Agent::class, 'parent_agent_id'); }
    public function children(): HasMany { return $this->hasMany(Agent::class, 'parent_agent_id'); }
    public function commissionEntries(): HasMany { return $this->hasMany(CommissionEntry::class); }
    public function liquidations(): HasMany { return $this->hasMany(CommissionLiquidation::class); }

    public function uniqueIds(): array { return ['uuid']; }
}
