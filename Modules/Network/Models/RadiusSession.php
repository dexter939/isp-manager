<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusSession extends Model
{
    protected $fillable = [
        'tenant_id',
        'radius_user_id',
        'username',
        'nas_ip',
        'nas_port_id',
        'framed_ip',
        'framed_ipv6',
        'acct_session_id',
        'acct_unique_id',
        'acct_start',
        'acct_stop',
        'acct_session_time',
        'acct_input_octets',
        'acct_output_octets',
        'acct_terminate_cause',
        'service_type',
        'calling_station_id',
        'called_station_id',
        'retention_until',
    ];

    protected $casts = [
        'acct_start'          => 'datetime',
        'acct_stop'           => 'datetime',
        'retention_until'     => 'date',
        'acct_input_octets'   => 'integer',
        'acct_output_octets'  => 'integer',
        'acct_session_time'   => 'integer',
    ];

    public function radiusUser(): BelongsTo
    {
        return $this->belongsTo(RadiusUser::class);
    }

    public function isActive(): bool
    {
        return is_null($this->acct_stop);
    }

    public function durationFormatted(): string
    {
        $seconds = $this->acct_session_time;
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('acct_stop');
    }

    public function scopeForUser($query, string $username)
    {
        return $query->where('username', $username);
    }
}
