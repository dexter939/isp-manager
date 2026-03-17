<?php

declare(strict_types=1);

namespace Modules\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class CustomerDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'contract_id', 'type', 'name',
        'disk', 'path', 'mime_type', 'size_bytes',
        'sha256', 'is_signed', 'signed_at',
    ];

    protected $casts = [
        'is_signed'  => 'boolean',
        'signed_at'  => 'datetime',
        'size_bytes' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /** URL temporaneo firmato per download sicuro (5 minuti) */
    public function temporaryUrl(int $minutes = 5): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes($minutes));
    }
}
