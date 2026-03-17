<?php

namespace Modules\Infrastructure\Topology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopologyLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'source_device_id' => $this->source_device_id,
            'target_device_id' => $this->target_device_id,
            'link_type'        => $this->link_type?->value,
            'status'           => $this->status?->value,
            'bandwidth_mbps'   => $this->bandwidth_mbps,
            'latency_ms'       => $this->latency_ms,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
