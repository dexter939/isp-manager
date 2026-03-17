<?php

declare(strict_types=1);

namespace Modules\Coverage\Data;

use Spatie\LaravelData\Data;

/**
 * DTO: risultato della verifica di fattibilità per un indirizzo.
 * Ritornato da FeasibilityService::check().
 * Zero chiamate API carrier — solo dati locali.
 */
class FeasibilityResult extends Data
{
    public function __construct(
        /** Indirizzo originale fornito dall'utente */
        public readonly string $inputAddress,

        /** Indirizzo normalizzato (via, civico, comune, provincia) */
        public readonly string $normalizedAddress,

        /** True se almeno una tecnologia è disponibile e vendibile */
        public readonly bool $hasCoverage,

        /**
         * Lista tecnologie disponibili ordinate per velocità desc.
         * @var TechnologyOption[]
         */
        public readonly array $technologies,

        /** codice_ui FiberCop (da usare per ordini FC) */
        public readonly ?string $codiceUiFibercop = null,

        /** id_building Open Fiber (da usare per ordini OF) */
        public readonly ?string $idBuildingOpenfiber = null,

        /** Distanza in metri dall'armadio FiberCop più vicino */
        public readonly ?int $distanceToCabinetM = null,

        /** Messaggio per l'operatore (es: "in costruzione", "non coperto") */
        public readonly ?string $note = null,

        /** Indica se il risultato viene dalla cache */
        public readonly bool $fromCache = false,
    ) {}

    /**
     * Ritorna solo le tecnologie vendibili, ordinate per velocità DL desc.
     *
     * @return TechnologyOption[]
     */
    public function availableTechnologies(): array
    {
        return collect($this->technologies)
            ->filter(fn(TechnologyOption $t) => $t->isAvailable())
            ->sortByDesc(fn(TechnologyOption $t) => $t->estimatedSpeedDl)
            ->values()
            ->all();
    }

    /**
     * Tecnologia migliore disponibile (più veloce e vendibile).
     */
    public function bestTechnology(): ?TechnologyOption
    {
        return collect($this->availableTechnologies())->first();
    }
}
