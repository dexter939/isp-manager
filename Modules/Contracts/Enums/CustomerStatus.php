<?php

declare(strict_types=1);

namespace Modules\Contracts\Enums;

enum CustomerStatus: string
{
    case Prospect   = 'prospect';
    case Active     = 'active';
    case Suspended  = 'suspended';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Prospect   => 'Prospect',
            self::Active     => 'Attivo',
            self::Suspended  => 'Sospeso',
            self::Terminated => 'Cessato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Prospect   => 'blue',
            self::Active     => 'green',
            self::Suspended  => 'yellow',
            self::Terminated => 'red',
        };
    }
}
