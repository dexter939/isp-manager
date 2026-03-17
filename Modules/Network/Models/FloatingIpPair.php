<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Network\Enums\FloatingIpStatus;

class FloatingIpPair extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'master_pppoe_account_id',
        'failover_pppoe_account_id',
        'status',
        'last_failover_at',
        'last_recovery_at',
    ];

    protected $casts = [
        'status'           => FloatingIpStatus::class,
        'last_failover_at' => 'datetime',
        'last_recovery_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function resources(): HasMany
    {
        return $this->hasMany(FloatingIpResource::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FloatingIpEvent::class)->orderByDesc('created_at');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', '!=', FloatingIpStatus::BothDown->value);
    }

    public function scopeInFailover($query)
    {
        return $query->where('status', FloatingIpStatus::FailoverActive->value);
    }
}
