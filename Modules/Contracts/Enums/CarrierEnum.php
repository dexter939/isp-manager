<?php

declare(strict_types=1);

namespace Modules\Contracts\Enums;

enum CarrierEnum: string
{
    case OpenFiber = 'openfiber';
    case FiberCop  = 'fibercop';
    case Fastweb   = 'fastweb';
    case Generic   = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::OpenFiber => 'Open Fiber',
            self::FiberCop  => 'FiberCop',
            self::Fastweb   => 'Fastweb',
            self::Generic   => 'Carrier Generico',
        };
    }

    /** Protocollo di integrazione */
    public function protocol(): string
    {
        return match ($this) {
            self::OpenFiber => 'SOAP/REST',
            self::FiberCop  => 'REST/OAuth2',
            self::Fastweb   => 'REST',
            self::Generic   => 'configurable',
        };
    }
}
