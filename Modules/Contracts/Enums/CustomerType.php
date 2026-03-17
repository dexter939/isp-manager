<?php

declare(strict_types=1);

namespace Modules\Contracts\Enums;

enum CustomerType: string
{
    case Privato = 'privato';
    case Azienda = 'azienda';

    public function label(): string
    {
        return match ($this) {
            self::Privato => 'Persona Fisica',
            self::Azienda => 'Persona Giuridica / Azienda',
        };
    }

    public function requiresPiva(): bool
    {
        return $this === self::Azienda;
    }
}
