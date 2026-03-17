<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PrepaidWalletStatus: string
{
    case Active    = 'active';
    case Suspended = 'suspended';
    case Exhausted = 'exhausted';

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Attivo',
            self::Suspended => 'Sospeso',
            self::Exhausted => 'Esaurito',
        };
    }
}
