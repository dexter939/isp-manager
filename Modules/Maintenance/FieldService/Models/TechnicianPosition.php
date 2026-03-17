<?php
namespace Modules\Maintenance\FieldService\Models;
use Illuminate\Database\Eloquent\Model;
class TechnicianPosition extends Model {
    protected $fillable = ['technician_id','latitude','longitude','accuracy_meters','recorded_at'];
    protected $casts = ['recorded_at'=>'datetime','latitude'=>'float','longitude'=>'float'];
}
