<?php

declare(strict_types=1);

namespace Modules\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Network\Models\CpeDevice;

class Tr069Parameter extends Model
{
    protected $fillable = [
        'tenant_id',
        'cpe_device_id',
        'parameter_path',
        'value',
        'type',
        'is_writable',
        'is_notification',
        'fetched_at',
    ];

    protected $casts = [
        'is_writable'      => 'boolean',
        'is_notification'  => 'boolean',
        'fetched_at'       => 'datetime',
    ];

    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }
}
