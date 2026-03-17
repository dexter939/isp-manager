<?php

declare(strict_types=1);

namespace Modules\Provisioning\Enums;

enum OrderType: string
{
    case Activation   = 'activation';
    case Change       = 'change';
    case Deactivation = 'deactivation';
    case Migration    = 'migration';

    public function label(): string
    {
        return match ($this) {
            self::Activation   => 'Attivazione',
            self::Change       => 'Variazione',
            self::Deactivation => 'Cessazione',
            self::Migration    => 'Migrazione FTTH',
        };
    }

    /** Messaggi SOAP OF corrispondenti */
    public function ofMessageType(): string
    {
        return match ($this) {
            self::Activation   => 'OLO_ActivationSetup_OpenStream',
            self::Change       => 'OLO_ChangeSetup_OpenStream',
            self::Deactivation => 'OLO_DeactivationOrder',
            self::Migration    => 'OLO_ActivationSetup_OpenStream', // con flag migrazione
        };
    }

    public function isCritical(): bool
    {
        return in_array($this, [self::Activation, self::Deactivation], true);
    }
}
