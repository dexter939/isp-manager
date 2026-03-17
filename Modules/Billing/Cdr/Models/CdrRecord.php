<?php
namespace Modules\Billing\Cdr\Models;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CdrRecord extends Model {
    protected $fillable = [
        'import_file_id','contract_id','customer_id','caller_number','called_number',
        'called_prefix','duration_seconds','start_time','end_time','category',
        'rate_per_minute_cents','connection_fee_cents','total_cost_cents','billed','invoice_id',
    ];
    protected $casts = ['start_time'=>'datetime','end_time'=>'datetime','billed'=>'boolean'];
    public function getTotalCostAttribute(): ?Money {
        if ($this->total_cost_cents === null) return null;
        return Money::ofMinor($this->total_cost_cents, 'EUR');
    }
    public function importFile(): BelongsTo { return $this->belongsTo(CdrImportFile::class,'import_file_id'); }
}
