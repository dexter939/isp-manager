<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalTicket extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'requested_by', 'assigned_to',
        'ticket_number', 'category', 'title', 'description',
        'status', 'priority',
        'opened_at', 'resolved_at', 'closed_at', 'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at'   => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at'   => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], strict: true);
    }
}
