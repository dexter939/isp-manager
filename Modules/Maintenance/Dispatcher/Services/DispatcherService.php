<?php
namespace Modules\Maintenance\Dispatcher\Services;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Maintenance\Dispatcher\Events\InterventionAssigned;
use Modules\Maintenance\Dispatcher\Models\DispatchAssignment;
class DispatcherService {
    public function getTechnicianTimeline(Carbon $date): array {
        $technicians = DB::table('users')->where('role', 'technician')->orWhereHas(fn() => DB::table('model_has_roles')->where('role_id', DB::table('roles')->where('name','technician')->value('id')))->get(['id','name','email','daily_capacity_hours','working_days']);
        $result = [];
        foreach ($technicians as $tech) {
            $assignments = DispatchAssignment::where('technician_id', $tech->id)->whereDate('scheduled_start', $date)->where('status', '!=', 'cancelled')->orderBy('scheduled_start')->get();
            $scheduledMinutes = $assignments->sum(fn($a) => $a->estimated_duration_minutes + $a->travel_time_minutes);
            $capacityMinutes  = ($tech->daily_capacity_hours ?? 8) * 60;
            $conflicts = $this->detectConflicts($assignments);
            $result[] = ['technician'=>$tech,'assignments'=>$assignments,'scheduled_minutes'=>$scheduledMinutes,'remaining_minutes'=>max(0, $capacityMinutes - $scheduledMinutes),'capacity_minutes'=>$capacityMinutes,'has_conflicts'=>count($conflicts) > 0,'conflicts'=>$conflicts];
        }
        return $result;
    }
    public function checkConflict(string $technicianId, Carbon $start, Carbon $end, ?string $excludeId = null): bool {
        $query = DispatchAssignment::where('technician_id', $technicianId)->where('status', '!=', 'cancelled')->where(function ($q) use ($start, $end) {
            $q->whereBetween('scheduled_start', [$start, $end])->orWhereBetween('scheduled_end', [$start, $end])->orWhere(fn($q2) => $q2->where('scheduled_start','<=',$start)->where('scheduled_end','>=',$end));
        });
        if ($excludeId) $query->where('id', '!=', $excludeId);
        return $query->exists();
    }
    public function assign(string $interventionId, string $technicianId, Carbon $start, int $durationMinutes, int $travelMinutes = 0): DispatchAssignment {
        $end = $start->copy()->addMinutes($durationMinutes + $travelMinutes);
        if ($this->checkConflict($technicianId, $start, $end)) {
            throw new \RuntimeException("Technician {$technicianId} has a scheduling conflict at {$start->toISOString()}");
        }
        $assignment = DispatchAssignment::create(['intervention_id'=>$interventionId,'technician_id'=>$technicianId,'scheduled_start'=>$start,'scheduled_end'=>$end,'estimated_duration_minutes'=>$durationMinutes,'travel_time_minutes'=>$travelMinutes,'assigned_by'=>auth()->id(),'status'=>'scheduled']);
        InterventionAssigned::dispatch($interventionId, $technicianId, $start);
        return $assignment;
    }
    public function reschedule(DispatchAssignment $assignment, Carbon $newStart): DispatchAssignment {
        $newEnd = $newStart->copy()->addMinutes($assignment->estimated_duration_minutes + ($assignment->travel_time_minutes ?? 0));
        if ($this->checkConflict($assignment->technician_id, $newStart, $newEnd, $assignment->id)) {
            throw new \RuntimeException("Conflict detected for technician at {$newStart->toISOString()}");
        }
        $assignment->update(['scheduled_start'=>$newStart,'scheduled_end'=>$newEnd]);
        return $assignment;
    }
    private function detectConflicts(Collection $assignments): array {
        $conflicts = [];
        $list      = $assignments->values();
        for ($i = 0; $i < $list->count(); $i++) {
            for ($j = $i + 1; $j < $list->count(); $j++) {
                $a = $list[$i]; $b = $list[$j];
                if ($a->scheduled_start < $b->scheduled_end && $a->scheduled_end > $b->scheduled_start) {
                    $conflicts[] = [$a->id, $b->id];
                }
            }
        }
        return $conflicts;
    }
}
