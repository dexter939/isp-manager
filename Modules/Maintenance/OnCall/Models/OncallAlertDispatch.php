<?php
namespace Modules\Maintenance\OnCall\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Maintenance\OnCall\Enums\DispatchStatus;
class OncallAlertDispatch extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'oncall_alert_dispatches';
    protected $guarded = ['id'];
    protected $casts = ['status'=>DispatchStatus::class,'notified_at'=>'datetime','acknowledged_at'=>'datetime','escalated_at'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
}
