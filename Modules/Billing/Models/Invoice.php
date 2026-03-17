<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Enums\InvoiceType;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'agent_id',
        'number',
        'sdi_progressive',
        'type',
        'period_from',
        'period_to',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'stamp_duty',
        'total',
        'status',
        'sdi_message_id',
        'sdi_filename',
        'sdi_status',
        'sdi_sent_at',
        'sdi_acknowledged_at',
        'sdi_raw_response',
        'pdf_path',
        'xml_path',
        'paid_at',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'type'                 => InvoiceType::class,
        'status'               => InvoiceStatus::class,
        'period_from'          => 'date',
        'period_to'            => 'date',
        'issue_date'           => 'date',
        'due_date'             => 'date',
        'subtotal'             => 'decimal:2',
        'tax_rate'             => 'decimal:2',
        'tax_amount'           => 'decimal:2',
        'stamp_duty'           => 'decimal:2',
        'total'                => 'decimal:2',
        'sdi_sent_at'          => 'datetime',
        'sdi_acknowledged_at'  => 'datetime',
        'paid_at'              => 'datetime',
        'notes'                => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function dunningSteps(): HasMany
    {
        return $this->hasMany(DunningStep::class)->orderBy('step');
    }

    // ── Money helpers ────────────────────────────────────────────────────────

    public function totalMoney(): Money
    {
        return Money::of((string) $this->total, 'EUR');
    }

    public function subtotalMoney(): Money
    {
        return Money::of((string) $this->subtotal, 'EUR');
    }

    // ── Business logic ───────────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Overdue
            || ($this->status->isPayable() && $this->due_date->isPast());
    }

    public function isPendingPayment(): bool
    {
        return $this->status->isPayable();
    }

    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return (int) Carbon::now()->diffInDays($this->due_date, false) * -1;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            InvoiceStatus::Issued->value,
            InvoiceStatus::SentSdi->value,
            InvoiceStatus::Overdue->value,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::Overdue->value);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeDueOn($query, \DateTimeInterface $date)
    {
        return $query->whereDate('due_date', $date);
    }

    // ── ActivityLog ──────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'paid_at', 'sdi_status', 'total'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
