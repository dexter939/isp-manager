<?php

declare(strict_types=1);

namespace Modules\Contracts\WizardMobile\Enums;

enum WizardStep: int
{
    case Cliente   = 0;
    case Indirizzo = 1;
    case Offerta   = 2;
    case Pagamento = 3;
    case Riepilogo = 4;
    case Otp       = 5;

    public function label(): string
    {
        return match ($this) {
            self::Cliente   => 'Dati Cliente',
            self::Indirizzo => 'Indirizzo',
            self::Offerta   => 'Offerta',
            self::Pagamento => 'Pagamento',
            self::Riepilogo => 'Riepilogo',
            self::Otp       => 'Firma OTP',
        };
    }

    public function isLast(): bool
    {
        return $this === self::Otp;
    }

    public function next(): ?self
    {
        $next = $this->value + 1;
        return self::tryFrom($next);
    }

    /**
     * Returns required field keys for each step.
     *
     * @return list<string>
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::Cliente   => ['nome', 'cognome', 'codice_fiscale', 'email', 'telefono'],
            self::Indirizzo => ['via', 'civico', 'comune', 'provincia', 'cap'],
            self::Offerta   => ['service_plan_id'],
            self::Pagamento => ['payment_method'],
            self::Riepilogo => [],
            self::Otp       => [],
        };
    }
}
