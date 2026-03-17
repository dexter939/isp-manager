<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Network\Enums\ParentalControlStatus;

class ParentalControlSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'pppoe_account_id',
        'profile_id',
        'status',
        'activated_at',
        'suspended_at',
        'customer_custom_blacklist',
        'customer_custom_whitelist',
    ];

    protected $casts = [
        'status'                    => ParentalControlStatus::class,
        'activated_at'              => 'datetime',
        'suspended_at'              => 'datetime',
        'customer_custom_blacklist' => 'array',
        'customer_custom_whitelist' => 'array',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query): mixed
    {
        return $query->where('status', ParentalControlStatus::Active->value);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ParentalControlProfile::class, 'profile_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ParentalControlLog::class, 'subscription_id');
    }
}
