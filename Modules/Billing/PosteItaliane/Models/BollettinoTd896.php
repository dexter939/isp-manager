<?php

namespace Modules\Billing\PosteItaliane\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BollettinoTd896 extends Model
{
    use HasUuids;

    protected $table = 'bollettini_td896';

    protected $fillable = [
        'uuid', 'invoice_id', 'customer_id', 'numero_bollettino',
        'importo_centesimi', 'causale', 'conto_corrente', 'status',
        'generated_at', 'paid_at', 'scadenza_at', 'reconciliation_file_id',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'paid_at'      => 'datetime',
        'scadenza_at'  => 'datetime',
    ];

    public function getImportoAttribute(): Money
    {
        return Money::ofMinor($this->importo_centesimi, 'EUR');
    }

    public function reconciliationFile(): BelongsTo
    {
        return $this->belongsTo(PosteReconciliationFile::class);
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
