<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Network\Enums\DnsFilterAction;

class ParentalControlLog extends Model
{
    // Bigint PK — no UUID for insert performance on high-volume log table
    public $incrementing = true;

    protected $keyType = 'int';

    // Append-only log table — no updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'queried_domain',
        'action',
        'blocked_reason',
        'client_ip',
        'queried_at',
    ];

    protected $casts = [
        'action'     => DnsFilterAction::class,
        'queried_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ParentalControlSubscription::class, 'subscription_id');
    }
}
