<?php
namespace Modules\Billing\Cdr\Models;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
class AnagrafeTributariaExport extends Model {
    protected $fillable = ['period_year','export_type','total_records','total_amount_cents','xml_path','generated_at','submitted_at'];
    protected $casts = ['generated_at'=>'datetime','submitted_at'=>'datetime'];
    public function getTotalAmountAttribute(): Money { return Money::ofMinor($this->total_amount_cents,'EUR'); }
}
