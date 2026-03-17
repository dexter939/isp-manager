<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PaymentStatus: string
{
    case Pending   = 'pending';
    case Completed = 'completed';
    case Failed    = 'failed';
    case Refunded  = 'refunded';
    case Disputed  = 'disputed';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'In attesa',
            self::Completed => 'Completato',
            self::Failed    => 'Fallito',
            self::Refunded  => 'Rimborsato',
            self::Disputed  => 'Contestato',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Refunded]);
    }
}
