<?php

declare(strict_types=1);

namespace Modules\Billing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Services\DunningService;

/**
 * Job schedulato ogni ora per eseguire i dunning steps in scadenza.
 *
 * Processa tutti i DunningStep con status=pending e scheduled_at <= now().
 */
class DunningJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(DunningService $dunningService): void
    {
        Log::info('DunningJob: avvio processing dunning steps');

        ['executed' => $executed, 'failed' => $failed] = $dunningService->processScheduledSteps();

        Log::info("DunningJob: completato — {$executed} eseguiti, {$failed} falliti");
    }
}
