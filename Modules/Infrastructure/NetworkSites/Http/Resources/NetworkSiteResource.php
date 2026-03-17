<?php

namespace Modules\Infrastructure\NetworkSites\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NetworkSiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'type'        => $this->type?->value,
            'status'      => $this->status?->value,
            'address'     => $this->address,
            'city'        => $this->city,
            'lat'         => $this->latitude,
            'lng'         => $this->longitude,
            'description' => $this->description,
            'stats'       => $this->whenLoaded('stats'),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
