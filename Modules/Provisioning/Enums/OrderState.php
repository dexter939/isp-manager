<?php

declare(strict_types=1);

namespace Modules\Provisioning\Enums;

enum OrderState: string
{
    case Draft       = 'draft';
    case Sent        = 'sent';
    case Accepted    = 'accepted';
    case Scheduled   = 'scheduled';
    case InProgress  = 'in_progress';
    case Completed   = 'completed';
    case Ko          = 'ko';
    case Cancelled   = 'cancelled';
    case Suspended   = 'suspended';
    case RetryFailed = 'retry_failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft       => 'Bozza',
            self::Sent        => 'Inviato al Carrier',
            self::Accepted    => 'Acquisito dal Carrier',
            self::Scheduled   => 'Pianificato',
            self::InProgress  => 'In lavorazione',
            self::Completed   => 'Completato',
            self::Ko          => 'KO',
            self::Cancelled   => 'Annullato',
            self::Suspended   => 'Sospeso',
            self::RetryFailed => 'Retry fallito',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft       => 'gray',
            self::Sent        => 'blue',
            self::Accepted    => 'cyan',
            self::Scheduled   => 'indigo',
            self::InProgress  => 'yellow',
            self::Completed   => 'green',
            self::Ko          => 'red',
            self::Cancelled   => 'red',
            self::Suspended   => 'orange',
            self::RetryFailed => 'red',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Ko, self::Cancelled, self::RetryFailed], true);
    }

    public function isRetryable(): bool
    {
        return in_array($this, [self::Ko, self::RetryFailed], true);
    }

    /**
     * Transizioni valide — mappate sui codici stato OF (STATO_ORDINE).
     * Vedi spec SPECINT v2.0/2.3.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft      => [self::Sent, self::Cancelled],
            self::Sent       => [self::Accepted, self::Ko, self::Cancelled],
            self::Accepted   => [self::Scheduled, self::Ko, self::Cancelled, self::Suspended],
            self::Scheduled  => [self::InProgress, self::Ko, self::Cancelled, self::Suspended],
            self::InProgress => [self::Completed, self::Ko, self::Suspended],
            self::Ko         => [self::Sent, self::Cancelled], // retry
            self::Suspended  => [self::Accepted, self::Cancelled], // desospensione
            self::RetryFailed=> [self::Cancelled],
            self::Completed  => [],
            self::Cancelled  => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Mappa codice stato OF → OrderState.
     * Vedi spec: 0=Acquisito, 1=AcquisitoKO, 2=Pianificato,
     * 3=Annullato, 4=Sospeso, 5=Espletato, 6=EspletataKO, 7=Rimodulato
     */
    public static function fromOfStatusCode(string $code): self
    {
        return match ($code) {
            '0'  => self::Accepted,
            '1'  => self::Ko,
            '2'  => self::Scheduled,
            '3'  => self::Cancelled,
            '4'  => self::Suspended,
            '5'  => self::Completed,
            '6'  => self::Ko,
            '7'  => self::Scheduled, // Rimodulato → rimane scheduled con nuova data
            '8'  => self::Accepted,  // Modificato OK
            '9'  => self::Ko,        // Modificato KO
            default => throw new \UnexpectedValueException("Codice stato OF sconosciuto: {$code}"),
        };
    }
}
