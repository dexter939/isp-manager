<?php

namespace Modules\Maintenance\OnCall\Http\Controllers;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maintenance\OnCall\Http\Requests\AcknowledgeAlertRequest;
use Modules\Maintenance\OnCall\Http\Requests\StoreScheduleRequest;
use Modules\Maintenance\OnCall\Http\Requests\StoreSlotRequest;
use Modules\Maintenance\OnCall\Models\OncallAlertDispatch;
use Modules\Maintenance\OnCall\Models\OncallSchedule;
use Modules\Maintenance\OnCall\Models\OncallScheduleSlot;
use Modules\Maintenance\OnCall\Services\OnCallService;
use Modules\Maintenance\OnCall\Http\Resources\OncallScheduleResource;

class OnCallController extends ApiController
{
    public function __construct(private OnCallService $service) {}

    public function current(): JsonResponse
    {
        $slot = $this->service->getCurrentOnCall(now());
        if (!$slot) {
            return $this->success(['on_call' => null, 'message' => 'No on-call technician currently scheduled']);
        }
        $user = \Illuminate\Support\Facades\DB::table('users')->find($slot->user_id);
        return $this->success(['slot' => $slot, 'user' => $user, 'since' => $slot->start_datetime, 'until' => $slot->end_datetime]);
    }

    public function weekSchedule(string $date): JsonResponse
    {
        $start = Carbon::parse($date)->startOfWeek();
        $end   = $start->copy()->endOfWeek();
        $slots = OncallScheduleSlot::whereBetween('start_datetime', [$start, $end])
            ->orderBy('start_datetime')
            ->with('schedule')
            ->get();
        return $this->success($slots);
    }

    public function acknowledge(AcknowledgeAlertRequest $request, string $alertId): JsonResponse
    {
        $data     = $request->validated();
        $dispatch = $this->service->acknowledge($data['dispatch_id'], auth()->id());
        return $this->success($dispatch);
    }

    public function dispatches(Request $request): JsonResponse
    {
        $dispatches = OncallAlertDispatch::when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('notified_at')
            ->paginate(25);
        return $this->success($dispatches);
    }

    // Schedule CRUD
    public function schedules(): JsonResponse
    {
        return $this->success(OncallScheduleResource::collection(OncallSchedule::with('slots')->paginate(20)));
    }

    public function storeSchedule(StoreScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();
        return $this->created(new OncallScheduleResource(OncallSchedule::create($data)));
    }

    public function storeSlot(StoreSlotRequest $request, OncallSchedule $schedule): JsonResponse
    {
        $data = $request->validated();
        $slot = OncallScheduleSlot::create(array_merge($data, ['schedule_id' => $schedule->id]));
        return $this->created($slot);
    }
}
