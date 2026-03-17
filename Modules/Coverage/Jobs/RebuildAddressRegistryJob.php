<?php

declare(strict_types=1);

namespace Modules\Coverage\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ricostruisce la tabella address_registry dopo ogni import di copertura.
 * Fa un UPSERT join tra coverage_fibercop e coverage_openfiber,
 * calcola la distanza all'armadio più vicino e aggiorna le flag di copertura.
 */
class RebuildAddressRegistryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout  = 7200; // 2 ore per ricostruzione completa
    public int $tries    = 2;
    public string $queue = 'imports';

    public function handle(): void
    {
        Log::info('RebuildAddressRegistry: avvio ricostruzione');
        $start = now();

        // Upsert da FiberCop
        DB::statement("
            INSERT INTO address_registry (
                comune, provincia, cap,
                via_normalizzata, civico_normalizzato,
                coverage_fibercop_id,
                has_ftth_fibercop, has_fttc, has_fwa,
                last_rebuilt_at, created_at, updated_at
            )
            SELECT
                cf.comune,
                cf.provincia,
                cf.cap,
                cf.via_normalizzata,
                cf.civico_normalizzato,
                cf.id AS coverage_fibercop_id,
                (cf.tecnologia = 'FTTH') AS has_ftth_fibercop,
                (cf.tecnologia IN ('FTTC','EVDSL')) AS has_fttc,
                (cf.tecnologia = 'FWA') AS has_fwa,
                NOW(),
                NOW(),
                NOW()
            FROM coverage_fibercop cf
            WHERE cf.via_normalizzata IS NOT NULL
              AND cf.civico_normalizzato IS NOT NULL
            ON CONFLICT (comune, provincia, via_normalizzata, civico_normalizzato)
            DO UPDATE SET
                coverage_fibercop_id = EXCLUDED.coverage_fibercop_id,
                has_ftth_fibercop    = EXCLUDED.has_ftth_fibercop,
                has_fttc             = EXCLUDED.has_fttc OR address_registry.has_fttc,
                has_fwa              = EXCLUDED.has_fwa OR address_registry.has_fwa,
                last_rebuilt_at      = NOW(),
                updated_at           = NOW()
        ");

        // Upsert da Open Fiber
        DB::statement("
            INSERT INTO address_registry (
                comune, provincia, cap,
                via_normalizzata, civico_normalizzato,
                coverage_openfiber_id,
                has_ftth_openfiber,
                last_rebuilt_at, created_at, updated_at
            )
            SELECT
                of2.comune,
                of2.provincia,
                of2.cap,
                of2.via_normalizzata,
                of2.civico_normalizzato,
                of2.id AS coverage_openfiber_id,
                (of2.tecnologia = 'FTTH') AS has_ftth_openfiber,
                NOW(),
                NOW(),
                NOW()
            FROM coverage_openfiber of2
            WHERE of2.via_normalizzata IS NOT NULL
              AND of2.civico_normalizzato IS NOT NULL
            ON CONFLICT (comune, provincia, via_normalizzata, civico_normalizzato)
            DO UPDATE SET
                coverage_openfiber_id = EXCLUDED.coverage_openfiber_id,
                has_ftth_openfiber    = EXCLUDED.has_ftth_openfiber,
                last_rebuilt_at       = NOW(),
                updated_at            = NOW()
        ");

        // Calcola distanza all'armadio FiberCop più vicino (per FTTC speed estimate)
        DB::statement("
            UPDATE address_registry ar
            SET distance_to_cabinet_m = subq.dist
            FROM (
                SELECT
                    ar2.id,
                    CAST(
                        ST_Distance(
                            ST_Transform(cf.geom, 32632),
                            ST_Transform(c.geom, 32632)
                        ) AS INTEGER
                    ) AS dist
                FROM address_registry ar2
                JOIN coverage_fibercop cf ON cf.id = ar2.coverage_fibercop_id
                JOIN LATERAL (
                    SELECT geom
                    FROM cabinets
                    WHERE carrier = 'fibercop'
                    ORDER BY geom <-> cf.geom
                    LIMIT 1
                ) c ON TRUE
                WHERE cf.geom IS NOT NULL
                  AND c.geom IS NOT NULL
            ) subq
            WHERE ar.id = subq.id
        ");

        $duration = now()->diffInSeconds($start);
        Log::info("RebuildAddressRegistry completato in {$duration}s");
    }
}
