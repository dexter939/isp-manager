<?php

declare(strict_types=1);

namespace Modules\Provisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierEventLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'carrier', 'direction', 'method_name',
        'carrier_order_id', 'codice_ordine_olo',
        'payload', 'http_status', 'ack_nack',
        'error_message', 'duration_ms', 'source_ip', 'logged_at',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'logged_at'   => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CarrierOrder::class, 'carrier_order_id');
    }

    public function scopeOutbound($query): mixed
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeInbound($query): mixed
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeNack($query): mixed
    {
        return $query->where('ack_nack', 'nack');
    }
}
