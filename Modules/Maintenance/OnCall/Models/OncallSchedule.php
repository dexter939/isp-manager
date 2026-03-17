<?php
namespace Modules\Maintenance\OnCall\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OncallSchedule extends Model {
    use HasUuids;
    protected $table = 'oncall_schedules';
    protected $guarded = ['id'];
    public function uniqueIds(): array { return ['id']; }
    public function slots() { return $this->hasMany(OncallScheduleSlot::class, 'schedule_id'); }
}
