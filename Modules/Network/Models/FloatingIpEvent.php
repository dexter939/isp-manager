<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Network\Enums\FloatingIpEventType;

class FloatingIpEvent extends Model
{
    use HasUuids;

    /** No updated_at column — append-only event log. */
    const UPDATED_AT = null;

    protected $fillable = [
        'floating_ip_pair_id',
        'event_type',
        'triggered_by',
        'previous_status',
        'new_status',
        'notes',
    ];

    protected $casts = [
        'event_type' => FloatingIpEventType::class,
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function floatingIpPair(): BelongsTo
    {
        return $this->belongsTo(FloatingIpPair::class);
    }
}
