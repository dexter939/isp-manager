<?php
namespace Modules\Maintenance\FieldService\Models;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FieldMaterial extends Model {
    protected $fillable = ['intervention_id','inventory_item_id','description','quantity','unit_cost_cents','serial_number'];
    public function getUnitCostAttribute(): ?Money { return $this->unit_cost_cents ? Money::ofMinor($this->unit_cost_cents,'EUR') : null; }
    public function intervention(): BelongsTo { return $this->belongsTo(FieldIntervention::class); }
}
