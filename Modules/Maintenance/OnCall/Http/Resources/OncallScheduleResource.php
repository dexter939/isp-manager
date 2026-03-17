<?php

namespace Modules\Maintenance\OnCall\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OncallScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'name'                        => $this->name,
            'escalation_timeout_minutes'  => $this->escalation_timeout_minutes,
            'slots'                       => $this->whenLoaded('slots', fn() => $this->slots->map(fn($slot) => [
                'id'             => $slot->id,
                'user_id'        => $slot->user_id,
                'start_datetime' => $slot->start_datetime instanceof \Carbon\Carbon
                    ? $slot->start_datetime->toIso8601String()
                    : $slot->start_datetime,
                'end_datetime'   => $slot->end_datetime instanceof \Carbon\Carbon
                    ? $slot->end_datetime->toIso8601String()
                    : $slot->end_datetime,
            ])),
            'created_at'                  => $this->created_at?->toIso8601String(),
            'updated_at'                  => $this->updated_at?->toIso8601String(),
        ];
    }
}
