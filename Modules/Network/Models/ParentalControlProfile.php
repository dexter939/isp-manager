<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentalControlProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_default',
        'blocked_categories',
        'custom_blacklist',
        'custom_whitelist',
        'agcom_compliant',
    ];

    protected $casts = [
        'is_default'         => 'boolean',
        'blocked_categories' => 'array',
        'custom_blacklist'   => 'array',
        'custom_whitelist'   => 'array',
        'agcom_compliant'    => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAgcomCompliant($query): mixed
    {
        return $query->where('agcom_compliant', true);
    }

    public function scopeIsDefault($query): mixed
    {
        return $query->where('is_default', true);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ParentalControlSubscription::class, 'profile_id');
    }
}
