<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BtsStation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'lat',
        'lng',
        'address',
        'ip_management',
        'status',
        'max_clients',
    ];

    public function sectors(): HasMany
    {
        return $this->hasMany(BtsSector::class);
    }

    public function cpeDevices(): HasMany
    {
        return $this->hasMany(CpeDevice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
