<?php

namespace Modules\Network\FairUsage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrafficUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pppoe_account_id' => $this->pppoe_account_id,
            'period_month'     => $this->period_month,
            'bytes_upload'     => $this->bytes_upload,
            'bytes_download'   => $this->bytes_download,
            'bytes_total'      => $this->bytes_total,
            'fup_status'       => $this->fup_status?->value ?? ($this->fup_triggered ? 'triggered' : 'ok'),
            'fup_applied_at'   => isset($this->fup_triggered_at) && $this->fup_triggered_at instanceof \Carbon\Carbon
                ? $this->fup_triggered_at->toIso8601String()
                : $this->fup_triggered_at,
            'cap_gb'           => $this->cap_gb,
        ];
    }
}
