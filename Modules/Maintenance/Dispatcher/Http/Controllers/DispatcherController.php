<?php

namespace Modules\Maintenance\Dispatcher\Http\Controllers;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Maintenance\Dispatcher\Http\Requests\StoreDispatchAssignmentRequest;
use Modules\Maintenance\Dispatcher\Http\Requests\UpdateDispatchAssignmentRequest;
use Modules\Maintenance\Dispatcher\Models\DispatchAssignment;
use Modules\Maintenance\Dispatcher\Services\DispatcherService;

class DispatcherController extends ApiController
{
    public function __construct(private DispatcherService $service) {}

    public function timeline(string $date): JsonResponse
    {
        return $this->success($this->service->getTechnicianTimeline(Carbon::parse($date)));
    }

    public function technicianTimeline(string $date, string $userId): JsonResponse
    {
        $assignments = DispatchAssignment::where('technician_id', $userId)
            ->whereDate('scheduled_start', Carbon::parse($date))
            ->orderBy('scheduled_start')
            ->get();
        return $this->success(['technician_id' => $userId, 'date' => $date, 'assignments' => $assignments]);
    }

    public function store(StoreDispatchAssignmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $assignment = $this->service->assign(
                $data['intervention_id'],
                $data['technician_id'],
                Carbon::parse($data['scheduled_start']),
                $data['estimated_duration_minutes'],
                $data['travel_time_minutes'] ?? 0
            );
            return $this->created($assignment);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateDispatchAssignmentRequest $request, DispatchAssignment $assignment): JsonResponse
    {
        $data = $request->validated();
        try {
            if (isset($data['estimated_duration_minutes'])) {
                $assignment->update([
                    'estimated_duration_minutes' => $data['estimated_duration_minutes'],
                    'travel_time_minutes'        => $data['travel_time_minutes'] ?? $assignment->travel_time_minutes,
                ]);
            }
            $updated = $this->service->reschedule($assignment, Carbon::parse($data['scheduled_start']));
            return $this->success($updated);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(DispatchAssignment $assignment): JsonResponse
    {
        $assignment->update(['status' => 'cancelled']);
        return $this->noContent();
    }

    public function unassigned(): JsonResponse
    {
        $assigned = DispatchAssignment::where('status', '!=', 'cancelled')->pluck('intervention_id');
        $interventions = \Illuminate\Support\Facades\DB::table('field_interventions')
            ->whereNotIn('id', $assigned)
            ->where('status', 'scheduled')
            ->orderBy('created_at')
            ->get();
        return $this->success($interventions);
    }
}
