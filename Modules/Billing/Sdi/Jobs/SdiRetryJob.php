<?php

namespace Modules\Billing\Sdi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Sdi\Models\SdiTransmission;
use Modules\Billing\Sdi\Services\SdiTransmissionService;

class SdiRetryJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(SdiTransmissionService $service): void
    {
        $maxRetries = config('sdi.max_retries', 3);

        SdiTransmission::query()
            ->whereIn('status', ['sent', 'error'])
            ->where('retry_count', '<', $maxRetries)
            ->whereNull('notification_code')
            ->orWhere('notification_code', 'MC') // Mancata Consegna = retry
            ->where('retry_count', '<', $maxRetries)
            ->each(function (SdiTransmission $transmission) use ($service) {
                try {
                    $service->retry($transmission);
                } catch (\Throwable $e) {
                    logger()->error('SDI retry failed', [
                        'transmission_id' => $transmission->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }
}
