<?php

declare(strict_types=1);

namespace Modules\Coverage\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Coverage\Data\FeasibilityResult;
use Modules\Coverage\Data\TechnologyOption;

/**
 * Verifica la fattibilità tecnica per un indirizzo.
 *
 * STRATEGIA: zero chiamate API carrier.
 * Tutto risolto da DB locale (coverage_fibercop + coverage_openfiber +
 * address_registry) aggiornato settimanalmente via ImportCoverageJob.
 *
 * Cache: 7 giorni per indirizzo (TTL configurabile via COVERAGE_ADDRESS_CACHE_TTL).
 */
class FeasibilityService
{
    private int $cacheTtl;

    public function __construct(
        private readonly AddressNormalizer $normalizer,
    ) {
        $this->cacheTtl = (int) config('core.cache_ttl.coverage_address', 604800);
    }

    /**
     * Verifica copertura per un indirizzo.
     * ZERO chiamate API carrier — solo DB locale.
     *
     * @param string $via       Es: "Via Roma" / "V.LE DELLE TERME"
     * @param string $civico    Es: "10" / "3/A"
     * @param string $comune    Es: "Bari" / "NAPOLI"
     * @param string $provincia Es: "BA" / "Na"
     */
    public function check(
        string $via,
        string $civico,
        string $comune,
        string $provincia,
    ): FeasibilityResult {
        // Normalizza input
        $normalizedVia      = $this->normalizer->normalizeVia($via);
        $normalizedCivico   = $this->normalizer->normalizeCivico($civico);
        $normalizedComune   = $this->normalizer->normalizeComune($comune);
        $normalizedProvincia = $this->normalizer->normalizeProvincia($provincia);

        $normalizedAddress = "{$normalizedVia} {$normalizedCivico}, {$normalizedComune} ({$normalizedProvincia})";
        $inputAddress      = "{$via} {$civico}, {$comune} ({$provincia})";

        $cacheKey = $this->buildCacheKey($normalizedVia, $normalizedCivico, $normalizedComune, $normalizedProvincia);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use (
            $normalizedVia, $normalizedCivico, $normalizedComune, $normalizedProvincia,
            $normalizedAddress, $inputAddress
        ): FeasibilityResult {
            return $this->queryDatabase(
                via: $normalizedVia,
                civico: $normalizedCivico,
                comune: $normalizedComune,
                provincia: $normalizedProvincia,
                normalizedAddress: $normalizedAddress,
                inputAddress: $inputAddress,
            );
        });
    }

    /**
     * Verifica copertura tramite coordinate GPS (per mappa Leaflet).
     * Cerca l'indirizzo più vicino nel raggio di radiusM metri.
     */
    public function checkByCoordinates(float $lat, float $lng, int $radiusM = 50): ?FeasibilityResult
    {
        // Cerca in coverage_fibercop il punto più vicino
        $fc = DB::selectOne("
            SELECT
                cf.codice_ui, cf.comune, cf.provincia, cf.via_normalizzata,
                cf.civico_normalizzato, cf.tecnologia, cf.velocita_max_dl,
                cf.velocita_max_ul, cf.stato_commerciale, cf.armadio_id,
                ST_Distance(ST_Transform(cf.geom, 32632), ST_Transform(ST_SetSRID(ST_MakePoint(?, ?), 4326), 32632)) AS dist_m
            FROM coverage_fibercop cf
            WHERE cf.geom IS NOT NULL
            ORDER BY cf.geom <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)
            LIMIT 1
        ", [$lng, $lat, $lng, $lat]);

        if (!$fc || $fc->dist_m > $radiusM) {
            return null;
        }

        return $this->check(
            via: $fc->via_normalizzata,
            civico: $fc->civico_normalizzato,
            comune: $fc->comune,
            provincia: $fc->provincia,
        );
    }

    /**
     * Ritorna GeoJSON con tutti i punti di copertura per una provincia/tecnologia.
     * Usato dall'endpoint mappa Leaflet.
     *
     * @return array<string, mixed> GeoJSON FeatureCollection
     */
    public function getMapGeoJson(string $provincia, ?string $tecnologia = null): array
    {
        $query = DB::table('coverage_fibercop')
            ->select(DB::raw("
                json_build_object(
                    'type', 'Feature',
                    'geometry', ST_AsGeoJSON(geom)::json,
                    'properties', json_build_object(
                        'carrier', 'fibercop',
                        'tecnologia', tecnologia,
                        'stato', stato_commerciale,
                        'velocita_dl', velocita_max_dl,
                        'codice_ui', codice_ui
                    )
                ) AS feature
            "))
            ->where('provincia', strtoupper($provincia))
            ->whereNotNull('geom');

        if ($tecnologia) {
            $query->where('tecnologia', strtoupper($tecnologia));
        }

        $features = $query->pluck('feature')->map(fn($f) => json_decode($f, true))->all();

        return [
            'type'     => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * Query principale al DB per la verifica di fattibilità.
     */
    private function queryDatabase(
        string $via,
        string $civico,
        string $comune,
        string $provincia,
        string $normalizedAddress,
        string $inputAddress,
    ): FeasibilityResult {
        // 1. Cerca in address_registry (join pre-calcolato)
        $registry = DB::table('address_registry as ar')
            ->leftJoin('coverage_fibercop as cf', 'cf.id', '=', 'ar.coverage_fibercop_id')
            ->leftJoin('coverage_openfiber as of2', 'of2.id', '=', 'ar.coverage_openfiber_id')
            ->select([
                'ar.*',
                'cf.codice_ui as fc_codice_ui',
                'cf.tecnologia as fc_tecnologia',
                'cf.velocita_max_dl as fc_dl', 'cf.velocita_max_ul as fc_ul',
                'cf.stato_commerciale as fc_stato',
                'cf.armadio_id as fc_armadio',
                'of2.id_building as of_id_building',
                'of2.tecnologia as of_tecnologia',
                'of2.velocita_max_dl as of_dl', 'of2.velocita_max_ul as of_ul',
                'of2.stato_commerciale as of_stato',
            ])
            ->where('ar.via_normalizzata', $via)
            ->where('ar.civico_normalizzato', $civico)
            ->where('ar.comune', $comune)
            ->where('ar.provincia', $provincia)
            ->first();

        if (!$registry) {
            // Fallback: ricerca fuzzy full-text se address_registry non ha il record
            return $this->fallbackFuzzySearch($via, $civico, $comune, $provincia, $normalizedAddress, $inputAddress);
        }

        $technologies = [];

        // Aggiungi tecnologia FiberCop
        if ($registry->coverage_fibercop_id) {
            $technologies[] = $this->buildTechnologyOption(
                carrier: 'fibercop',
                technology: $registry->fc_tecnologia,
                maxDl: (int) $registry->fc_dl,
                maxUl: (int) $registry->fc_ul,
                commercialStatus: $registry->fc_stato,
                distanceM: $registry->distance_to_cabinet_m,
                cabinetId: $registry->fc_armadio,
                codiceUiFibercop: $registry->fc_codice_ui,
            );
        }

        // Aggiungi tecnologia Open Fiber
        if ($registry->coverage_openfiber_id) {
            $technologies[] = $this->buildTechnologyOption(
                carrier: 'openfiber',
                technology: $registry->of_tecnologia,
                maxDl: (int) $registry->of_dl,
                maxUl: (int) $registry->of_ul,
                commercialStatus: $registry->of_stato,
                idBuildingOpenfiber: $registry->of_id_building,
            );
        }

        $hasCoverage = !empty($technologies) && collect($technologies)
            ->some(fn(TechnologyOption $t) => $t->isAvailable());

        return new FeasibilityResult(
            inputAddress: $inputAddress,
            normalizedAddress: $normalizedAddress,
            hasCoverage: $hasCoverage,
            technologies: $technologies,
            codiceUiFibercop: $registry->fc_codice_ui ?? null,
            idBuildingOpenfiber: $registry->of_id_building ?? null,
            distanceToCabinetM: $registry->distance_to_cabinet_m,
            note: $hasCoverage ? null : 'Indirizzo presente in banca dati ma non commercialmente disponibile.',
        );
    }

    /**
     * Ricerca fuzzy con PostgreSQL full-text search quando la ricerca esatta fallisce.
     * Usa il ranking ts_rank per trovare la via più simile.
     */
    private function fallbackFuzzySearch(
        string $via,
        string $civico,
        string $comune,
        string $provincia,
        string $normalizedAddress,
        string $inputAddress,
    ): FeasibilityResult {
        // Ricerca FTS sulla via + stesso comune/provincia/civico
        $result = DB::selectOne("
            SELECT cf.codice_ui, cf.tecnologia, cf.velocita_max_dl, cf.velocita_max_ul,
                   cf.stato_commerciale, cf.armadio_id, cf.via_normalizzata,
                   ts_rank(to_tsvector('italian', cf.via_normalizzata), plainto_tsquery('italian', ?)) AS rank
            FROM coverage_fibercop cf
            WHERE cf.provincia = ?
              AND cf.comune = ?
              AND cf.civico_normalizzato = ?
              AND to_tsvector('italian', cf.via_normalizzata) @@ plainto_tsquery('italian', ?)
            ORDER BY rank DESC
            LIMIT 1
        ", [$via, $provincia, $comune, $civico, $via]);

        if (!$result || $result->rank < 0.05) {
            return new FeasibilityResult(
                inputAddress: $inputAddress,
                normalizedAddress: $normalizedAddress,
                hasCoverage: false,
                technologies: [],
                note: 'Indirizzo non trovato in banca dati. Aggiornamento settimanale ogni domenica.',
            );
        }

        $technology = $this->buildTechnologyOption(
            carrier: 'fibercop',
            technology: $result->tecnologia,
            maxDl: (int) $result->velocita_max_dl,
            maxUl: (int) $result->velocita_max_ul,
            commercialStatus: $result->stato_commerciale,
            cabinetId: $result->armadio_id,
            codiceUiFibercop: $result->codice_ui,
        );

        return new FeasibilityResult(
            inputAddress: $inputAddress,
            normalizedAddress: "Via trovata: {$result->via_normalizzata} {$civico}, {$comune} ({$provincia})",
            hasCoverage: $technology->isAvailable(),
            technologies: [$technology],
            codiceUiFibercop: $result->codice_ui,
            note: "Risultato approssimato (ricerca full-text). Via trovata: {$result->via_normalizzata}",
        );
    }

    /**
     * Costruisce un TechnologyOption calcolando la velocità stimata per FTTC/EVDSL.
     */
    private function buildTechnologyOption(
        string $carrier,
        string $technology,
        int $maxDl,
        int $maxUl,
        string $commercialStatus,
        ?int $distanceM = null,
        ?string $cabinetId = null,
        ?string $codiceUiFibercop = null,
        ?string $idBuildingOpenfiber = null,
    ): TechnologyOption {
        $estimatedDl = match ($technology) {
            'FTTC', 'EVDSL' => $distanceM !== null
                ? $this->estimateVdslSpeed($distanceM, $maxDl)
                : $maxDl,
            default => $maxDl,
        };

        return new TechnologyOption(
            carrier: $carrier,
            technology: $technology,
            maxSpeedDl: $maxDl,
            maxSpeedUl: $maxUl,
            estimatedSpeedDl: $estimatedDl,
            commercialStatus: $commercialStatus,
            cabinetId: $cabinetId,
            distanceToCabinetM: $distanceM,
            codiceUiFibercop: $codiceUiFibercop,
            idBuildingOpenfiber: $idBuildingOpenfiber,
        );
    }

    /**
     * Stima la velocità reale VDSL2/EVDSL in base alla distanza dall'armadio.
     *
     * Formula esponenziale da CLAUDE_CONTEXT.md:
     *   Vmax_stimata = Vnom * e^(-0.0023 * distanza_metri)
     *
     * Esempi:
     *   100 Mbps a 200m  → ~87 Mbps
     *   100 Mbps a 500m  → ~32 Mbps
     *   100 Mbps a 1000m → ~10 Mbps
     *   100 Mbps a 1500m → ~3 Mbps
     */
    private function estimateVdslSpeed(float $distanceMeters, int $nominalSpeedMbps): int
    {
        $estimated = $nominalSpeedMbps * exp(-0.0023 * $distanceMeters);

        return max(1, (int) round($estimated));
    }

    private function buildCacheKey(string $via, string $civico, string $comune, string $provincia): string
    {
        return 'feasibility:' . md5("{$via}|{$civico}|{$comune}|{$provincia}");
    }
}
