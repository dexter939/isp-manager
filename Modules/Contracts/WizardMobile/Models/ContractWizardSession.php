<?php

declare(strict_types=1);

namespace Modules\Contracts\WizardMobile\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\WizardMobile\Enums\WizardStatus;
use Modules\Contracts\WizardMobile\Enums\WizardStep;

class ContractWizardSession extends Model
{
    use HasUuids;

    protected $table = 'contract_wizard_sessions';

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'agent_id',
        'customer_id',
        'current_step',
        'step_data',
        'status',
        'otp_verified',
        'otp_code',
        'otp_expires_at',
        'completed_contract_id',
        'started_at',
        'last_activity_at',
        'completed_at',
        'expires_at',
    ];

    /** @var array<string, string|class-string> */
    protected $casts = [
        'current_step'   => 'integer',
        'step_data'      => 'array',
        'status'         => WizardStatus::class,
        'otp_verified'   => 'boolean',
        'otp_expires_at' => 'datetime',
        'started_at'     => 'datetime',
        'last_activity_at' => 'datetime',
        'completed_at'   => 'datetime',
        'expires_at'     => 'datetime',
    ];

    /**
     * The column used as the unique identifier for HasUuids.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // ---- Relazioni ----

    public function agent(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    public function completedContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'completed_contract_id');
    }

    // ---- Accessors / Business Logic ----

    public function currentStepEnum(): WizardStep
    {
        return WizardStep::from($this->current_step);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === WizardStatus::Completed;
    }

    public function isInProgress(): bool
    {
        return $this->status === WizardStatus::InProgress;
    }

    public function isAbandoned(): bool
    {
        return $this->status === WizardStatus::Abandoned;
    }

    public function isOtpExpired(): bool
    {
        return $this->otp_expires_at !== null && $this->otp_expires_at->isPast();
    }

    /**
     * Returns step data for a specific step.
     *
     * @return array<string, mixed>
     */
    public function getStepData(WizardStep $step): array
    {
        return $this->step_data[$step->name] ?? [];
    }

    // ---- Scopes ----

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeInProgress(mixed $query): mixed
    {
        return $query->where('status', WizardStatus::InProgress->value);
    }

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeExpired(mixed $query): mixed
    {
        return $query->where('expires_at', '<', Carbon::now());
    }
}
