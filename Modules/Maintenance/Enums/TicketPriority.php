<?php

declare(strict_types=1);

namespace Modules\Maintenance\Enums;

enum TicketPriority: string
{
    case Low      = 'low';
    case Medium   = 'medium';
    case High     = 'high';
    case Critical = 'critical';

    /** SLA ore per prima risposta */
    public function firstResponseHours(): int
    {
        return match($this) {
            self::Low      => 48,
            self::Medium   => 24,
            self::High     => 8,
            self::Critical => 2,
        };
    }

    /** SLA ore per risoluzione */
    public function resolutionHours(): int
    {
        return match($this) {
            self::Low      => 120,
            self::Medium   => 48,
            self::High     => 24,
            self::Critical => 8,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Low      => 'Bassa',
            self::Medium   => 'Media',
            self::High     => 'Alta',
            self::Critical => 'Critica',
        };
    }
}
