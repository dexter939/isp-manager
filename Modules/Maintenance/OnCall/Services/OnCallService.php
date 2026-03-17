<?php
namespace Modules\Maintenance\OnCall\Services;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Maintenance\OnCall\Jobs\EscalateAlertJob;
use Modules\Maintenance\OnCall\Models\OncallScheduleSlot;
use Modules\Maintenance\OnCall\Models\OncallAlertDispatch;
class OnCallService {
    public function getCurrentOnCall(Carbon $at): ?OncallScheduleSlot {
        return OncallScheduleSlot::where('level', 1)->where('start_datetime', '<=', $at)->where('end_datetime', '>=', $at)->orderBy('start_datetime', 'desc')->first();
    }
    public function getCurrentOnCallByLevel(Carbon $at, int $level): ?OncallScheduleSlot {
        return OncallScheduleSlot::where('level', $level)->where('start_datetime', '<=', $at)->where('end_datetime', '>=', $at)->orderBy('start_datetime', 'desc')->first();
    }
    public function dispatchAlert(object $alert): ?OncallAlertDispatch {
        $slot = $this->getCurrentOnCall(now());
        if (!$slot) { Log::warning("OnCallService: no on-call technician found for alert {$alert->id}"); return null; }
        $dispatch = OncallAlertDispatch::create(['monitoring_alert_id'=>$alert->id,'slot_id'=>$slot->id,'user_id'=>$slot->user_id,'level'=>1,'channel'=>'email','status'=>'pending','notified_at'=>now()]);
        $this->sendNotification($slot->user_id, $alert, 'email');
        // Schedule escalation
        $schedule = $slot->schedule;
        $timeout  = $schedule?->escalation_timeout_minutes ?? 15;
        EscalateAlertJob::dispatch($dispatch->id)->delay(now()->addMinutes($timeout));
        return $dispatch;
    }
    public function acknowledge(string $dispatchId, string $userId): OncallAlertDispatch {
        $dispatch = OncallAlertDispatch::findOrFail($dispatchId);
        if ($dispatch->user_id !== $userId) throw new \RuntimeException('Unauthorized acknowledgement');
        $dispatch->update(['status'=>'acknowledged','acknowledged_at'=>now()]);
        return $dispatch;
    }
    public function escalate(string $dispatchId): ?OncallAlertDispatch {
        $dispatch = OncallAlertDispatch::find($dispatchId);
        if (!$dispatch || $dispatch->status->value !== 'pending') return null;
        $nextLevel = $dispatch->level + 1;
        $nextSlot  = $this->getCurrentOnCallByLevel(now(), $nextLevel);
        $dispatch->update(['status'=>'escalated','escalated_at'=>now()]);
        if (!$nextSlot) {
            Log::warning("OnCallService: escalation level {$nextLevel} not configured, notifying all technicians");
            return null;
        }
        $alert = DB::table('monitoring_alerts')->find($dispatch->monitoring_alert_id);
        $newDispatch = OncallAlertDispatch::create(['monitoring_alert_id'=>$dispatch->monitoring_alert_id,'slot_id'=>$nextSlot->id,'user_id'=>$nextSlot->user_id,'level'=>$nextLevel,'channel'=>'sms','status'=>'pending','notified_at'=>now()]);
        $this->sendNotification($nextSlot->user_id, $alert, 'sms');
        $schedule = $nextSlot->schedule;
        $timeout  = $schedule?->escalation_timeout_minutes ?? 15;
        EscalateAlertJob::dispatch($newDispatch->id)->delay(now()->addMinutes($timeout));
        return $newDispatch;
    }
    private function sendNotification(string $userId, object $alert, string $channel): void {
        $user = DB::table('users')->find($userId);
        if (!$user) return;
        Log::info("OnCallService: notifying user {$user->email} via {$channel} for alert {$alert->id}");
        // In production: use Laravel Notifications (Mail, SMS via Twilio, etc.)
    }
}
