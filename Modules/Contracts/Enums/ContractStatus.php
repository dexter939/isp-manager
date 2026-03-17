<?php

declare(strict_types=1);

namespace Modules\Contracts\Enums;

enum ContractStatus: string
{
    case Draft            = 'draft';
    case PendingSignature = 'pending_signature';
    case Active           = 'active';
    case Suspended        = 'suspended';
    case Terminated       = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Draft            => 'Bozza',
            self::PendingSignature => 'In attesa di firma',
            self::Active           => 'Attivo',
            self::Suspended        => 'Sospeso',
            self::Terminated       => 'Cessato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft            => 'gray',
            self::PendingSignature => 'yellow',
            self::Active           => 'green',
            self::Suspended        => 'orange',
            self::Terminated       => 'red',
        };
    }

    public function isBillable(): bool
    {
        return $this === self::Active;
    }

    public function canBeSigned(): bool
    {
        return $this === self::PendingSignature;
    }

    /** Transizioni valide da questo stato */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft            => [self::PendingSignature],
            self::PendingSignature => [self::Active, self::Draft],
            self::Active           => [self::Suspended, self::Terminated],
            self::Suspended        => [self::Active, self::Terminated],
            self::Terminated       => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
