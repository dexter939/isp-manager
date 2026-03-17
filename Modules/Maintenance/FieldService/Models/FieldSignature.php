<?php
namespace Modules\Maintenance\FieldService\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FieldSignature extends Model {
    protected $fillable = ['intervention_id','signer_type','signer_name','signature_path','otp_code','otp_verified_at','signed_at'];
    protected $casts = ['otp_verified_at'=>'datetime','signed_at'=>'datetime'];
    public function intervention(): BelongsTo { return $this->belongsTo(FieldIntervention::class); }
}
