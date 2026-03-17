<?php

declare(strict_types=1);

namespace Modules\Contracts\Enums;

enum PaymentMethod: string
{
    case Sdd       = 'sdd';
    case Carta     = 'carta';
    case Bonifico  = 'bonifico';
    case Contanti  = 'contanti';

    public function label(): string
    {
        return match ($this) {
            self::Sdd      => 'Addebito Diretto SEPA (SDD)',
            self::Carta    => 'Carta di Credito / Debito',
            self::Bonifico => 'Bonifico Bancario',
            self::Contanti => 'Contanti',
        };
    }

    public function requiresIban(): bool
    {
        return $this === self::Sdd;
    }

    public function requiresStripe(): bool
    {
        return $this === self::Carta;
    }

    public function supportsAutoCharge(): bool
    {
        return in_array($this, [self::Sdd, self::Carta], true);
    }
}
