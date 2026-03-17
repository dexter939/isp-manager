<?php

namespace Modules\Billing\PosteItaliane\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosteReconciliationFile extends Model
{
    protected $fillable = [
        'filename', 'imported_at', 'records_total',
        'records_matched', 'records_unmatched', 'raw_content',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function bollettini(): HasMany
    {
        return $this->hasMany(BollettinoTd896::class, 'reconciliation_file_id');
    }
}
