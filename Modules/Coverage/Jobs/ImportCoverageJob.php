<?php

declare(strict_types=1);

namespace Modules\Coverage\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use League\Csv\Reader;
use Modules\Coverage\Services\AddressNormalizer;

/**
 * Importa il file di copertura CSV (FiberCop NetMap o Open Fiber bulk)
 * in modo chunked tramite LazyCollection per gestire file da centinaia di MB
 * senza esaurire la memoria.
 *
 * Schedulato: settimanale (domenica 02:00 FC, lunedì 03:00 OF).
 */
class ImportCoverageJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout   = 3600;  // 1 ora per file grandi
    public int $tries     = 3;
    public string $queue  = 'imports';

    private const CHUNK_SIZE = 500;

    // Mappa colonne CSV → campo DB per FiberCop NetMap v6.1
    private const FIBERCOP_COLUMN_MAP = [
        'codice_ui'          => 'codice_ui',
        'comune'             => 'comune',
        'provincia'          => 'provincia',
        'cap'                => 'cap',
        'via'                => 'via',
        'civico'             => 'civico',
        'tecnologia'         => 'tecnologia',
        'velocita_max_dl'    => 'velocita_max_dl',
        'velocita_max_ul'    => 'velocita_max_ul',
        'stato_commerciale'  => 'stato_commerciale',
        'armadio_id'         => 'armadio_id',
        'coordinate_lat'     => '__lat',
        'coordinate_lng'     => '__lng',
    ];

    // Mappa colonne CSV → campo DB per Open Fiber bulk
    private const OPENFIBER_COLUMN_MAP = [
        'id_building'    => 'id_building',
        'codice_ui'      => 'codice_ui_of',
        'comune'         => 'comune',
        'provincia'      => 'provincia',
        'cap'            => 'cap',
        'via'            => 'via',
        'civico'         => 'civico',
        'tecnologia'     => 'tecnologia',
        'velocita_dl'    => 'velocita_max_dl',
        'velocita_ul'    => 'velocita_max_ul',
        'stato_comm'     => 'stato_commerciale',
        'lat'            => '__lat',
        'lng'            => '__lng',
    ];

    public function __construct(
        private readonly string $carrier,   // 'fibercop' | 'openfiber'
        private readonly string $filePath,  // path su disco (storage o MinIO)
        private readonly string $disk = 'local',
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(AddressNormalizer $normalizer): void
    {
        $logId = $this->startImportLog();

        $counters = ['processed' => 0, 'inserted' => 0, 'updated' => 0, 'failed' => 0];

        try {
            $localPath = $this->resolveLocalPath();
            $reader    = $this->buildCsvReader($localPath);
            $colMap    = $this->carrier === 'fibercop'
                ? self::FIBERCOP_COLUMN_MAP
                : self::OPENFIBER_COLUMN_MAP;

            $table = $this->carrier === 'fibercop' ? 'coverage_fibercop' : 'coverage_openfiber';

            // LazyCollection per processare CSV senza caricare tutto in memoria
            $this->makeLazyCollection($reader)
                ->chunk(self::CHUNK_SIZE)
                ->each(function (LazyCollection $chunk) use ($table, $colMap, $normalizer, &$counters, $logId): void {
                    $rows = [];

                    foreach ($chunk as $record) {
                        $counters['processed']++;

                        try {
                            $row = $this->mapRecord($record, $colMap, $normalizer);
                            if ($row !== null) {
                                $rows[] = $row;
                            }
                        } catch (\Throwable $e) {
                            $counters['failed']++;
                            Log::warning("CoverageImport row error [{$this->carrier}]: " . $e->getMessage());
                        }
                    }

                    if (empty($rows)) {
                        return;
                    }

                    // Upsert chunk — in PostgreSQL usa ON CONFLICT DO UPDATE
                    $uniqueKey = $this->carrier === 'fibercop' ? 'codice_ui' : 'id_building';
                    $affected  = $this->upsertChunk($table, $rows, $uniqueKey);

                    $counters['updated']  += $affected['updated'];
                    $counters['inserted'] += $affected['inserted'];

                    // Aggiorna log progress ogni chunk
                    DB::table('coverage_import_logs')->where('id', $logId)->update([
                        'rows_processed' => $counters['processed'],
                        'rows_inserted'  => $counters['inserted'],
                        'rows_updated'   => $counters['updated'],
                        'rows_failed'    => $counters['failed'],
                        'updated_at'     => now(),
                    ]);
                });

            $this->completeImportLog($logId, $counters);

            // Triggera rebuild del registro indirizzi normalizzato
            RebuildAddressRegistryJob::dispatch()->onQueue('imports');

        } catch (\Throwable $e) {
            $this->failImportLog($logId, $e->getMessage(), $counters);
            throw $e;
        }
    }

    /**
     * Mappa un record CSV nei campi del DB con normalizzazione.
     *
     * @param array<string, string> $record
     * @param array<string, string> $colMap
     * @return array<string, mixed>|null
     */
    private function mapRecord(array $record, array $colMap, AddressNormalizer $normalizer): ?array
    {
        $row = [];

        foreach ($colMap as $csvCol => $dbCol) {
            $value = trim($record[$csvCol] ?? '');
            if (!str_starts_with($dbCol, '__')) {
                $row[$dbCol] = $value ?: null;
            }
        }

        // Normalizzazione toponomastica
        $row['via_normalizzata']   = $normalizer->normalizeVia($row['via'] ?? '');
        $row['civico_normalizzato'] = $normalizer->normalizeCivico($row['civico'] ?? '');

        // Geometria PostGIS da lat/lng
        $lat = (float) ($record[$this->getLngCol('lat')] ?? 0);
        $lng = (float) ($record[$this->getLngCol('lng')] ?? 0);

        $row['geom'] = ($lat !== 0.0 && $lng !== 0.0)
            ? DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)")
            : null;

        $row['imported_at'] = now();
        $row['source_file'] = basename($this->filePath);
        $row['updated_at']  = now();
        $row['created_at']  = now();

        // Normalizza stato_commerciale verso valori attesi
        $row['stato_commerciale'] = match (strtolower($row['stato_commerciale'] ?? '')) {
            'vendibile', 'commercializzabile', 'v', '1' => 'vendibile',
            'in costruzione', 'in_costruzione', 'c'     => 'in_costruzione',
            default                                      => 'non_vendibile',
        };

        return $row;
    }

    /**
     * Upsert chunk nel DB usando INSERT ... ON CONFLICT DO UPDATE.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{inserted: int, updated: int}
     */
    private function upsertChunk(string $table, array $rows, string $uniqueKey): array
    {
        if (empty($rows)) {
            return ['inserted' => 0, 'updated' => 0];
        }

        // Conta record esistenti prima dell'upsert per calcolare inserted vs updated
        $existingKeys = DB::table($table)
            ->whereIn($uniqueKey, array_column($rows, $uniqueKey))
            ->pluck($uniqueKey)
            ->all();

        $existingCount = count($existingKeys);

        // Rimuovi geom dalle colonne (gestita a parte con raw)
        $updateColumns = array_filter(
            array_keys($rows[0]),
            fn($k) => !in_array($k, ['id', 'created_at', $uniqueKey, 'geom'], true)
        );

        DB::table($table)->upsert(
            $rows,
            uniqueBy: [$uniqueKey],
            update: array_values($updateColumns),
        );

        $inserted = count($rows) - $existingCount;
        $updated  = $existingCount;

        return ['inserted' => max(0, $inserted), 'updated' => max(0, $updated)];
    }

    private function buildCsvReader(string $localPath): Reader
    {
        $reader = Reader::createFromPath($localPath, 'r');
        $reader->setHeaderOffset(0);

        // Rileva delimitatore automaticamente (CSV = comma, NetMap = pipe)
        $firstLine = file($localPath, FILE_IGNORE_NEW_LINES)[0] ?? '';
        if (substr_count($firstLine, '|') > substr_count($firstLine, ',')) {
            $reader->setDelimiter('|');
        }

        return $reader;
    }

    /**
     * Crea una LazyCollection dal CSV Reader per processamento memory-efficient.
     */
    private function makeLazyCollection(Reader $reader): LazyCollection
    {
        return LazyCollection::make(function () use ($reader) {
            foreach ($reader->getRecords() as $record) {
                yield $record;
            }
        });
    }

    private function resolveLocalPath(): string
    {
        if ($this->disk === 'local' && file_exists($this->filePath)) {
            return $this->filePath;
        }

        // Scarica da storage (MinIO/S3) in temp locale
        $tempPath = sys_get_temp_dir() . '/' . basename($this->filePath);
        $contents = Storage::disk($this->disk)->get($this->filePath);
        file_put_contents($tempPath, $contents);

        return $tempPath;
    }

    private function getLngCol(string $type): string
    {
        $map = $this->carrier === 'fibercop'
            ? ['lat' => 'coordinate_lat', 'lng' => 'coordinate_lng']
            : ['lat' => 'lat', 'lng' => 'lng'];

        return $map[$type];
    }

    private function startImportLog(): int
    {
        return DB::table('coverage_import_logs')->insertGetId([
            'carrier'     => $this->carrier,
            'source_file' => $this->filePath,
            'status'      => 'running',
            'started_at'  => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * @param array{processed: int, inserted: int, updated: int, failed: int} $counters
     */
    private function completeImportLog(int $logId, array $counters): void
    {
        DB::table('coverage_import_logs')->where('id', $logId)->update([
            'status'           => 'completed',
            'rows_processed'   => $counters['processed'],
            'rows_inserted'    => $counters['inserted'],
            'rows_updated'     => $counters['updated'],
            'rows_failed'      => $counters['failed'],
            'completed_at'     => now(),
            'duration_seconds' => now()->diffInSeconds(
                DB::table('coverage_import_logs')->where('id', $logId)->value('started_at')
            ),
            'updated_at' => now(),
        ]);

        Log::info("CoverageImport [{$this->carrier}] completato", $counters);
    }

    /**
     * @param array{processed: int, inserted: int, updated: int, failed: int} $counters
     */
    private function failImportLog(int $logId, string $error, array $counters): void
    {
        DB::table('coverage_import_logs')->where('id', $logId)->update([
            'status'         => 'failed',
            'error_message'  => $error,
            'rows_processed' => $counters['processed'],
            'rows_failed'    => $counters['failed'],
            'completed_at'   => now(),
            'updated_at'     => now(),
        ]);
    }
}
