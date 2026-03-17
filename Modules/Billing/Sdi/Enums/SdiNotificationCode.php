<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Enums;

enum SdiNotificationCode: string
{
    case RC = 'RC'; // Ricevuta di Consegna — delivered to recipient
    case MC = 'MC'; // Mancata Consegna — retry
    case NS = 'NS'; // Notifica di Scarto — rejected (schema error)
    case EC = 'EC'; // Esito Committente — buyer accepted/rejected
    case AT = 'AT'; // Attestazione di Trasmissione — SDI received
    case DT = 'DT'; // Decorrenza Termini — timeout accepted
    case SF = 'SF'; // Scarto Fattura — header error

    public function toStatus(): SdiStatus
    {
        return match($this) {
            self::RC, self::AT => SdiStatus::Delivered,
            self::MC           => SdiStatus::Sent, // keep retrying
            self::NS, self::SF => SdiStatus::Rejected,
            self::EC, self::DT => SdiStatus::Accepted,
        };
    }

    public function description(): string
    {
        return match($this) {
            self::RC => 'Ricevuta di Consegna',
            self::MC => 'Mancata Consegna',
            self::NS => 'Notifica di Scarto',
            self::EC => 'Esito Committente',
            self::AT => 'Attestazione di Trasmissione',
            self::DT => 'Decorrenza Termini',
            self::SF => 'Scarto Fattura',
        };
    }
}
