<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use App\Exceptions\ApiException;

class ApiQuotaExceededException extends ApiException
{
    public function __construct(
        public readonly string $carrier,
        public readonly string $callType,
        public readonly int $currentCount,
        public readonly int $dailyLimit,
        string $message = '',
    ) {
        $message = $message ?: sprintf(
            'API quota exceeded for carrier "%s" call type "%s": %d/%d calls used today (%.1f%%)',
            $carrier,
            $callType,
            $currentCount,
            $dailyLimit,
            $dailyLimit > 0 ? ($currentCount / $dailyLimit) * 100 : 0,
        );

        parent::__construct($message, 429);
    }
}
