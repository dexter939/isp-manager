<?php
namespace Modules\Maintenance\FieldService\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FieldActivity extends Model {
    protected $fillable = ['intervention_id','description','duration_minutes'];
    public function intervention(): BelongsTo { return $this->belongsTo(FieldIntervention::class); }
}
