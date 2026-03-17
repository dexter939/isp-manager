<?php

declare(strict_types=1);

namespace Modules\Maintenance\Enums;

enum TicketStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Pending    = 'pending';    // in attesa di risposta carrier / cliente
    case Resolved   = 'resolved';
    case Closed     = 'closed';
    case Cancelled  = 'cancelled';

    /** @return array<self> */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Open       => [self::InProgress, self::Pending, self::Resolved, self::Cancelled],
            self::InProgress => [self::Pending, self::Resolved, self::Closed, self::Cancelled],
            self::Pending    => [self::InProgress, self::Resolved, self::Closed],
            self::Resolved   => [self::Closed, self::Open],  // riapertura se il problema persiste
            self::Closed     => [self::Open],
            self::Cancelled  => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::Pending], strict: true);
    }

    public function label(): string
    {
        return match($this) {
            self::Open       => 'Aperto',
            self::InProgress => 'In lavorazione',
            self::Pending    => 'In attesa',
            self::Resolved   => 'Risolto',
            self::Closed     => 'Chiuso',
            self::Cancelled  => 'Annullato',
        };
    }
}
