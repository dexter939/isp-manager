<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Enums;

enum DunningStatus: string
{
    case Open       = 'open';
    case Resolved   = 'resolved';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match($this) {
            self::Open       => 'Aperto',
            self::Resolved   => 'Risolto',
            self::Terminated => 'Terminato',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Open;
    }
}
