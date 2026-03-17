<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TroubleTicket extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'customer_id', 'contract_id', 'assigned_to',
        'ticket_number', 'title', 'description',
        'status', 'priority', 'type', 'source',
        'carrier', 'carrier_ticket_id',
        'opened_at', 'first_response_at', 'resolved_at', 'closed_at', 'due_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'status'            => TicketStatus::class,
            'priority'          => TicketPriority::class,
            'opened_at'         => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at'       => 'datetime',
            'closed_at'         => 'datetime',
            'due_at'            => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'priority', 'assigned_to', 'carrier_ticket_id'])
            ->logOnlyDirty()
            ->useLogName('ticket');
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class, 'ticket_id');
    }

    // ── Business logic ────────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && $this->isOpen();
    }

    public function slaFirstResponseBreached(): bool
    {
        if ($this->first_response_at) {
            return false;
        }
        $deadline = $this->opened_at->copy()->addHours($this->priority->firstResponseHours());
        return now()->isAfter($deadline);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::Pending->value,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->open()->where('due_at', '<', now());
    }
}
