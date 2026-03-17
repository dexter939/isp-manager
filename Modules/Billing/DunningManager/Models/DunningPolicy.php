<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DunningPolicy extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'steps',
        'active',
    ];

    protected $casts = [
        'steps'      => 'array',
        'is_default' => 'boolean',
        'active'     => 'boolean',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(DunningCase::class, 'policy_id');
    }

    /**
     * Returns the step definition at the given index.
     */
    public function getStep(int $index): ?array
    {
        return $this->steps[$index] ?? null;
    }

    /**
     * Returns the total number of steps in this policy.
     */
    public function stepCount(): int
    {
        return count($this->steps ?? []);
    }

    /**
     * Scope: only active policies.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope: default policy.
     */
    public function scopeDefault(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_default', true);
    }
}
