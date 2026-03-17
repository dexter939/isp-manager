<?php

declare(strict_types=1);

namespace Modules\Coverage\Data;

use Spatie\LaravelData\Data;

/**
 * DTO: singola opzione tecnologica disponibile per un indirizzo.
 */
class TechnologyOption extends Data
{
    public function __construct(
        /** Carrier: 'fibercop', 'openfiber', 'fastweb' */
        public readonly string $carrier,

        /** Tecnologia: 'FTTH', 'FTTC', 'EVDSL', 'FWA' */
        public readonly string $technology,

        /** Velocità nominale massima in download (Mbps) */
        public readonly int $maxSpeedDl,

        /** Velocità nominale massima in upload (Mbps) */
        public readonly int $maxSpeedUl,

        /**
         * Velocità reale stimata in download (Mbps).
         * Per FTTH = uguale a maxSpeedDl.
         * Per FTTC/EVDSL = calcolata con formula esponenziale sulla distanza.
         */
        public readonly int $estimatedSpeedDl,

        /** Stato commerciale: 'vendibile', 'in_costruzione', 'non_vendibile' */
        public readonly string $commercialStatus,

        /** ID armadio FiberCop (se FTTC/EVDSL) */
        public readonly ?string $cabinetId = null,

        /** Distanza in metri dall'armadio (se calcolata) */
        public readonly ?int $distanceToCabinetM = null,

        /** codice_ui FiberCop (per ordini) */
        public readonly ?string $codiceUiFibercop = null,

        /** id_building Open Fiber (per ordini) */
        public readonly ?string $idBuildingOpenfiber = null,
    ) {}

    /**
     * Ritorna true se la tecnologia è disponibile e vendibile.
     */
    public function isAvailable(): bool
    {
        return $this->commercialStatus === 'vendibile';
    }

    /**
     * Label human-readable per la tecnologia.
     */
    public function label(): string
    {
        return match ($this->technology) {
            'FTTH'  => 'Fibra FTTH (fino a ' . $this->maxSpeedDl . ' Mbps)',
            'FTTC'  => 'FTTC - Fibra misto rame (stima ' . $this->estimatedSpeedDl . ' Mbps)',
            'EVDSL' => 'Enhanced VDSL2 (stima ' . $this->estimatedSpeedDl . ' Mbps)',
            'FWA'   => 'Fixed Wireless Access (' . $this->maxSpeedDl . ' Mbps)',
            default => $this->technology . ' (' . $this->maxSpeedDl . ' Mbps)',
        };
    }
}
