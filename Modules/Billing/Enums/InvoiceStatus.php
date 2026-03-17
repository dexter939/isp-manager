<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum InvoiceStatus: string
{
    case Draft     = 'draft';
    case Issued    = 'issued';
    case SentSdi   = 'sent_sdi';
    case Paid      = 'paid';
    case Overdue   = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Bozza',
            self::Issued    => 'Emessa',
            self::SentSdi   => 'Inviata SDI',
            self::Paid      => 'Pagata',
            self::Overdue   => 'Scaduta',
            self::Cancelled => 'Annullata',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Issued    => 'blue',
            self::SentSdi   => 'indigo',
            self::Paid      => 'green',
            self::Overdue   => 'red',
            self::Cancelled => 'yellow',
        };
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::Issued, self::SentSdi, self::Overdue]);
    }

    public function allowedTransitions(): array
    {
        return match($this) {
            self::Draft     => [self::Issued, self::Cancelled],
            self::Issued    => [self::SentSdi, self::Paid, self::Overdue, self::Cancelled],
            self::SentSdi   => [self::Paid, self::Overdue, self::Cancelled],
            self::Overdue   => [self::Paid, self::Cancelled],
            self::Paid      => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
