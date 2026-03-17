<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Enums;

enum DunningAction: string
{
    case Email     = 'email';
    case Sms       = 'sms';
    case Whatsapp  = 'whatsapp';
    case Suspend   = 'suspend';
    case Terminate = 'terminate';

    public function isNotification(): bool
    {
        return match($this) {
            self::Email, self::Sms, self::Whatsapp => true,
            default                                 => false,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Email     => 'Email',
            self::Sms       => 'SMS',
            self::Whatsapp  => 'WhatsApp',
            self::Suspend   => 'Sospensione',
            self::Terminate => 'Terminazione',
        };
    }
}
