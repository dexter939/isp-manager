<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id', 'user_id', 'body', 'type',
        'is_internal', 'is_ai_generated', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_internal'    => 'boolean',
            'is_ai_generated' => 'boolean',
            'metadata'       => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TroubleTicket::class, 'ticket_id');
    }
}
