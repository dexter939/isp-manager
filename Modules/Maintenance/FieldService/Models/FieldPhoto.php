<?php
namespace Modules\Maintenance\FieldService\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FieldPhoto extends Model {
    protected $fillable = ['intervention_id','photo_path','taken_at','description'];
    protected $casts = ['taken_at'=>'datetime'];
    public function intervention(): BelongsTo { return $this->belongsTo(FieldIntervention::class); }
}
