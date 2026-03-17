<?php

declare(strict_types=1);

namespace Modules\Contracts\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Annual  = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensile',
            self::Annual  => 'Annuale',
        };
    }

    public function monthsPerPeriod(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Annual  => 12,
        };
    }
}
