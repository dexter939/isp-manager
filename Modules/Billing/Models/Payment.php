<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Enums\PaymentStatus;

class Payment extends Model
{
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'customer_id',
        'method',
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_error',
        'sepa_mandate_id',
        'sepa_end_to_end_id',
        'sepa_return_code',
        'sepa_file_id',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'status'       => PaymentStatus::class,
        'amount'       => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    protected $hidden = ['stripe_error'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sepaFile(): BelongsTo
    {
        return $this->belongsTo(SepaFile::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::Completed;
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::Pending->value);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::Completed->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::Failed->value);
    }
}
