<?php

declare(strict_types=1);

namespace Modules\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSignature extends Model
{
    protected $fillable = [
        'contract_id', 'otp_hash', 'otp_channel', 'otp_sent_to',
        'otp_sent_at', 'otp_expires_at', 'otp_verified_at', 'otp_used',
        'signer_ip', 'signer_user_agent', 'signed_at',
        'pdf_hash_pre_firma', 'pdf_hash_post_firma',
        'status', 'failure_reason', 'failed_attempts',
    ];

    protected $casts = [
        'otp_sent_at'      => 'datetime',
        'otp_expires_at'   => 'datetime',
        'otp_verified_at'  => 'datetime',
        'signed_at'        => 'datetime',
        'otp_used'         => 'boolean',
        'failed_attempts'  => 'integer',
    ];

    /** I campi hash non vengono mai loggati */
    protected $hidden = ['otp_hash'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isExpired(): bool
    {
        return $this->otp_expires_at !== null && $this->otp_expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
