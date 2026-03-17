<?php
namespace Modules\Maintenance\OnCall\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OncallScheduleSlot extends Model {
    use HasUuids;
    protected $table = 'oncall_schedule_slots';
    protected $guarded = ['id'];
    protected $casts = ['start_datetime'=>'datetime','end_datetime'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function schedule() { return $this->belongsTo(OncallSchedule::class, 'schedule_id'); }
}
