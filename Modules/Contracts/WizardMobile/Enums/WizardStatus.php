<?php

declare(strict_types=1);

namespace Modules\Contracts\WizardMobile\Enums;

enum WizardStatus: string
{
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Abandoned  = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Corso',
            self::Completed  => 'Completato',
            self::Abandoned  => 'Abbandonato',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Abandoned => true,
            self::InProgress                 => false,
        };
    }
}
