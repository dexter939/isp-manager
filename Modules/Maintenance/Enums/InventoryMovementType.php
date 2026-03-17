<?php

declare(strict_types=1);

namespace Modules\Maintenance\Enums;

enum InventoryMovementType: string
{
    case In         = 'in';          // carico da fornitore
    case Out        = 'out';         // scarico per installazione / guasto
    case Transfer   = 'transfer';    // trasferimento tra magazzini
    case Adjustment = 'adjustment';  // rettifica inventario

    /** Segno della quantità: +1 per entrata, -1 per uscita */
    public function quantitySign(): int
    {
        return match($this) {
            self::In         => 1,
            self::Out        => -1,
            self::Transfer   => 0,   // gestito separatamente (da/a)
            self::Adjustment => 0,   // il valore è esplicito
        };
    }

    public function label(): string
    {
        return match($this) {
            self::In         => 'Carico',
            self::Out        => 'Scarico',
            self::Transfer   => 'Trasferimento',
            self::Adjustment => 'Rettifica',
        };
    }
}
