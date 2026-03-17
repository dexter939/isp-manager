<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum DunningAction: string
{
    case EmailReminder   = 'email_reminder';
    case SmsReminder     = 'sms_reminder';
    case WhatsAppReminder = 'whatsapp_reminder';
    case Suspension      = 'suspension';
    case RetrySdd        = 'retry_sdd';
    case Termination     = 'termination';

    public function label(): string
    {
        return match($this) {
            self::EmailReminder    => 'Reminder email',
            self::SmsReminder      => 'Reminder SMS',
            self::WhatsAppReminder => 'Reminder WhatsApp',
            self::Suspension       => 'Sospensione servizio',
            self::RetrySdd         => 'Secondo tentativo SDD',
            self::Termination      => 'Cessazione contratto',
        };
    }

    /** Giorno dalla scadenza fattura in cui eseguire questa azione */
    public function dayOffset(): int
    {
        return match($this) {
            self::EmailReminder    => 10,
            self::SmsReminder      => 15,
            self::WhatsAppReminder => 20,
            self::Suspension       => 25,
            self::RetrySdd         => 30,
            self::Termination      => 45,
        };
    }
}
