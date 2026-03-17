<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Network\Enums\FloatingIpResourceType;

class FloatingIpResource extends Model
{
    use HasUuids;

    protected $fillable = [
        'floating_ip_pair_id',
        'resource_type',
        'resource_value',
    ];

    protected $casts = [
        'resource_type' => FloatingIpResourceType::class,
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function floatingIpPair(): BelongsTo
    {
        return $this->belongsTo(FloatingIpPair::class);
    }
}
