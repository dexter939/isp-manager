<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Enums;

enum SdiStatus: string
{
    case Pending   = 'pending';
    case Sent      = 'sent';
    case Delivered = 'delivered';
    case Rejected  = 'rejected';
    case Accepted  = 'accepted';
    case Error     = 'error';

    public function isTerminal(): bool
    {
        return match($this) {
            self::Accepted, self::Rejected => true,
            default                        => false,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'In attesa',
            self::Sent      => 'Inviata',
            self::Delivered => 'Consegnata',
            self::Rejected  => 'Rifiutata',
            self::Accepted  => 'Accettata',
            self::Error     => 'Errore',
        };
    }
}
