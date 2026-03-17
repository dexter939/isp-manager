<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiusProfile extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'vendor',
        'rate_dl_kbps',
        'rate_ul_kbps',
        'walled_dl_kbps',
        'walled_ul_kbps',
        'mikrotik_rate_limit',
        'cisco_qos_policy_in',
        'cisco_qos_policy_out',
        'address_pool',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'rate_dl_kbps' => 'integer',
        'rate_ul_kbps' => 'integer',
    ];

    public function radiusUsers(): HasMany
    {
        return $this->hasMany(RadiusUser::class);
    }

    /** Genera il valore Mikrotik-Rate-Limit da dl/ul kbps */
    public function mikrotikRateLimit(): string
    {
        if ($this->mikrotik_rate_limit) {
            return $this->mikrotik_rate_limit;
        }
        $dl = $this->formatBandwidth($this->rate_dl_kbps);
        $ul = $this->formatBandwidth($this->rate_ul_kbps);
        return "{$dl}/{$ul}";
    }

    /** Genera il valore per walled garden */
    public function mikrotikWalledGardenLimit(): string
    {
        return $this->formatBandwidth($this->walled_dl_kbps)
            . '/' . $this->formatBandwidth($this->walled_ul_kbps);
    }

    private function formatBandwidth(int $kbps): string
    {
        if ($kbps >= 1_000_000) {
            return round($kbps / 1_000_000) . 'G';
        }
        if ($kbps >= 1_000) {
            return round($kbps / 1_000) . 'M';
        }
        return $kbps . 'k';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
