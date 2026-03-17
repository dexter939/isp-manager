<?php

declare(strict_types=1);

namespace Modules\Provisioning\Data;

use Spatie\LaravelData\Data;

/**
 * Risultato Line Test Open Fiber (v2.3) o FiberCop statusZpoint.
 */
class LineStatusResult extends Data
{
    public function __construct(
        public readonly bool    $success,
        public readonly string  $result,            // OK | KO
        public readonly ?string $ontOperationalState, // UP | DOWN | POWER OFF
        public readonly ?float  $attenuation,         // dBm
        public readonly ?float  $opticalDistance,     // metri
        public readonly ?string $ontLanStatus,        // ENABLED | DISABLED
        public readonly ?string $errorCode   = null,  // L01-L07
        public readonly ?string $errorDescription = null,
        public readonly ?int    $testInstanceId = null,
        public readonly bool    $isRetryable = false,
        public readonly bool    $requiresTicket = false, // L02 unreachable → apri ticket
    ) {}

    public static function fromOfV23Response(array $data): self
    {
        $result = $data['Result'] ?? 'KO';
        $code   = $data['Code'] ?? null;
        $desc   = $data['Description'] ?? null;

        // v2.3: L02 distingue timeout (retry) da unreachable (ticket)
        $isRetryable    = false;
        $requiresTicket = false;

        if ($code === 'L02') {
            $desc = strtolower($desc ?? '');
            if (str_contains($desc, 'timeout')) {
                $isRetryable = true;
            } elseif (str_contains($desc, 'unreachable')) {
                $requiresTicket = true;
            } else {
                $isRetryable = true; // retry conservativo
            }
        } elseif ($code === 'L01') {
            $isRetryable = true;
        }

        return new self(
            success: $result === 'OK',
            result: $result,
            ontOperationalState: $data['OntOperationalState'] ?? null,
            attenuation: isset($data['Attenuation']) ? (float) $data['Attenuation'] : null,
            opticalDistance: isset($data['OpticalDistance']) ? (float) $data['OpticalDistance'] : null,
            ontLanStatus: $data['OntLanStatus'] ?? null,
            errorCode: $code,
            errorDescription: $data['Description'] ?? null,
            testInstanceId: isset($data['TestInstanceId']) ? (int) $data['TestInstanceId'] : null,
            isRetryable: $isRetryable,
            requiresTicket: $requiresTicket,
        );
    }
}
