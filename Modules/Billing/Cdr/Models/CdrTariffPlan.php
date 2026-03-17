<?php
namespace Modules\Billing\Cdr\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CdrTariffPlan extends Model {
    protected $fillable = ['name', 'description', 'is_default', 'active'];
    protected $casts = ['is_default' => 'boolean', 'active' => 'boolean'];
    public function rates(): HasMany { return $this->hasMany(CdrRate::class, 'tariff_plan_id'); }
}
