<?php

namespace Modules\Monitoring\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NetworkAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'device_id'       => $this->cpe_device_id,
            'severity'        => $this->severity,
            'type'            => $this->type,
            'message'         => $this->message,
            'status'          => $this->status,
            'suppressed'      => $this->suppressed ?? false,
            'triggered_at'    => $this->created_at?->toIso8601String(),
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
            'resolved_at'     => $this->resolved_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
