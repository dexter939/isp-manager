<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\DunningManager\Enums\DunningAction;

class DunningStep extends Model
{
    protected $fillable = [
        'case_id',
        'step_index',
        'action',
        'executed_at',
        'result',
        'notes',
    ];

    protected $casts = [
        'action'      => DunningAction::class,
        'executed_at' => 'datetime',
        'step_index'  => 'integer',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(DunningCase::class, 'case_id');
    }
}
