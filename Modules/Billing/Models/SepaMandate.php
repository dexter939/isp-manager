<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Models\Customer;

class SepaMandate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'mandate_id',
        'signed_at',
        'sequence_type',
        'iban',
        'bic',
        'account_holder',
        'creditor_id',
        'status',
        'revoked_at',
        'revocation_reason',
    ];

    protected $casts = [
        'iban'           => 'encrypted',
        'account_holder' => 'encrypted',
        'signed_at'      => 'date',
        'revoked_at'     => 'datetime',
    ];

    protected $hidden = ['iban', 'account_holder'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function revoke(string $reason): void
    {
        $this->update([
            'status'            => 'revoked',
            'revoked_at'        => now(),
            'revocation_reason' => $reason,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
