<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Exceptions;

use App\Exceptions\ApiException;

class SdiTransmissionException extends ApiException
{
    public static function channelFailed(string $channel, string $reason): self
    {
        return new self("SDI channel '{$channel}' failed: {$reason}", 502);
    }

    public static function alreadyTerminal(string $transmissionId): self
    {
        return new self("Transmission {$transmissionId} is already in a terminal state and cannot be retried.", 409);
    }

    public static function maxRetriesExceeded(string $transmissionId, int $maxRetries): self
    {
        return new self("Transmission {$transmissionId} has exceeded the maximum retry count of {$maxRetries}.", 422);
    }
}
