<?php
namespace Modules\Maintenance\Dispatcher\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Maintenance\Dispatcher\Database\Factories\DispatchAssignmentFactory;
class DispatchAssignment extends Model {
    use HasUuids, HasFactory;
    protected static function newFactory(): DispatchAssignmentFactory { return DispatchAssignmentFactory::new(); }
    protected $table = 'dispatch_assignments';
    protected $guarded = ['id'];
    protected $casts = ['scheduled_start'=>'datetime','scheduled_end'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function getTotalMinutesAttribute(): int {
        return $this->estimated_duration_minutes + ($this->travel_time_minutes ?? 0);
    }
}
