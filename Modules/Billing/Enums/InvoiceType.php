<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum InvoiceType: string
{
    case Fattura           = 'TD01';
    case NotaCredito       = 'TD04';
    case FatturaSemplificata = 'TD07';

    public function label(): string
    {
        return match($this) {
            self::Fattura              => 'Fattura (TD01)',
            self::NotaCredito          => 'Nota di credito (TD04)',
            self::FatturaSemplificata  => 'Fattura semplificata (TD07)',
        };
    }
}
