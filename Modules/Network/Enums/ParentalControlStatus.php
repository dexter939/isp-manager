<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum ParentalControlStatus: string
{
    case Active    = 'active';
    case Suspended = 'suspended';
    case Pending   = 'pending';

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Attivo',
            self::Suspended => 'Sospeso',
            self::Pending   => 'In attesa',
        };
    }
}
