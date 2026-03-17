<?php
namespace Modules\Maintenance\FieldService\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class FieldIntervention extends Model {
    use HasUuids;
    protected $fillable = [
        'uuid','ticket_id','contract_id','customer_id','technician_id',
        'intervention_type','status','scheduled_at','started_at','completed_at',
        'address','latitude','longitude','notes','verbale_path',
    ];
    protected $casts = ['scheduled_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime'];
    public function activities(): HasMany { return $this->hasMany(FieldActivity::class,'intervention_id'); }
    public function materials(): HasMany { return $this->hasMany(FieldMaterial::class,'intervention_id'); }
    public function photos(): HasMany { return $this->hasMany(FieldPhoto::class,'intervention_id'); }
    public function signatures(): HasMany { return $this->hasMany(FieldSignature::class,'intervention_id'); }
    public function uniqueIds(): array { return ['uuid']; }
}
