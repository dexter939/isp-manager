<?php

namespace Modules\Billing\Sdi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdiNotification extends Model
{
    protected $fillable = [
        'transmission_id', 'notification_type', 'received_at', 'raw_payload', 'processed',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed'   => 'boolean',
    ];

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(SdiTransmission::class, 'transmission_id');
    }

    public function getCodeEnumAttribute(): SdiNotificationCode
    {
        return SdiNotificationCode::from($this->notification_type);
    }
}
