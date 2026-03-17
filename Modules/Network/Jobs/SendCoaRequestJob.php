<?php

declare(strict_types=1);

namespace Modules\Network\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Invia un pacchetto CoA (CoA-Request o Disconnect-Request) al NAS
 * tramite radclient in modo asincrono — evita di bloccare il worker Octane.
 */
class SendCoaRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * @param array<string, string> $attrs
     */
    public function __construct(
        private readonly string $nasIp,
        private readonly array $attrs,
        private readonly string $packetType,
        private readonly string $coaSecret,
        private readonly int $coaPort,
    ) {}

    public function handle(): void
    {
        $input = collect($this->attrs)
            ->map(fn($v, $k) => "{$k} = \"{$v}\"")
            ->implode("\n");

        $target  = "{$this->nasIp}:{$this->coaPort}";
        $command = ['radclient', '-x', $target, $this->packetType, $this->coaSecret];

        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process     = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            Log::error("CoA {$this->packetType}: impossibile avviare radclient per {$this->nasIp}");
            $this->fail(new \RuntimeException("radclient non trovato o non eseguibile"));
            return;
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::error("CoA {$this->packetType} fallito (exit={$exitCode}) per {$this->nasIp}: {$stdout}{$stderr}");
            $this->fail(new \RuntimeException("radclient exit code {$exitCode}: {$stderr}"));
        } else {
            Log::debug("CoA {$this->packetType} OK per {$this->nasIp}");
        }
    }
}
