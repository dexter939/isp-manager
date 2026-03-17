<?php
namespace Modules\Billing\Cdr\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CdrImportFile extends Model {
    protected $fillable = ['filename','format','imported_at','records_imported','records_failed','status','error_message'];
    protected $casts = ['imported_at'=>'datetime'];
    public function records(): HasMany { return $this->hasMany(CdrRecord::class,'import_file_id'); }
}
