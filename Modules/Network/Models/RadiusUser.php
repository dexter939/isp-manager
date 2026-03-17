<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;

class RadiusUser extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'username',
        'password_hash',
        'auth_type',
        'radius_profile_id',
        'status',
        'walled_garden',
        'walled_garden_token',
        'nas_ip',
        'framed_ip',
        'acct_session_id',
        'last_auth_at',
    ];

    protected $casts = [
        'walled_garden' => 'boolean',
        'last_auth_at'  => 'datetime',
    ];

    protected $hidden = ['password_hash'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(RadiusProfile::class, 'radius_profile_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RadiusSession::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function setPassword(string $cleartext): void
    {
        $this->update(['password_hash' => Hash::make($cleartext)]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }
}
