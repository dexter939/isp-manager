<?php

namespace Modules\Billing\Sdi\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SdiTransmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid', 'invoice_id', 'channel', 'status', 'filename',
        'xml_content', 'xml_hash', 'sent_at', 'notification_code',
        'last_error', 'retry_count', 'conservazione_expires_at',
    ];

    protected $casts = [
        'sent_at'                  => 'datetime',
        'conservazione_expires_at' => 'datetime',
        'retry_count'              => 'integer',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(SdiNotification::class, 'transmission_id');
    }

    public function getStatusEnumAttribute(): SdiStatus
    {
        return SdiStatus::from($this->status);
    }

    public function isTerminal(): bool
    {
        return $this->getStatusEnumAttribute()->isTerminal();
    }

    public function canRetry(): bool
    {
        return !$this->isTerminal()
            && $this->retry_count < config('sdi.max_retries', 3);
    }

    /** @return string[] */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
