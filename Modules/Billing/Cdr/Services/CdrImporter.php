<?php

namespace Modules\Billing\Cdr\Services;

use Brick\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Modules\Billing\Cdr\Models\CdrImportFile;
use Modules\Billing\Cdr\Models\CdrRate;
use Modules\Billing\Cdr\Models\CdrRecord;

class CdrImporter
{
    /**
     * Imports CDR records from CSV content.
     * Supports asterisk, yeastar, generic formats.
     * Uses LazyCollection for memory efficiency.
     */
    public function import(string $content, string $filename, string $format = 'auto'): CdrImportFile
    {
        if ($format === 'auto') {
            $format = $this->detectFormat($content);
        }

        $importFile = CdrImportFile::create([
            'filename'    => $filename,
            'format'      => $format,
            'imported_at' => now(),
            'status'      => 'processing',
        ]);

        $imported = 0;
        $failed   = 0;

        try {
            $csv = Reader::createFromString($content);
            $csv->setHeaderOffset(0);
            $formatConfig = config("cdr.import_formats.{$format}", config('cdr.import_formats.generic'));

            $tariffPlanId = config('cdr.default_tariff_plan', 1);

            foreach ($csv->getRecords() as $row) {
                try {
                    $parsed = $this->parseRow($row, $format, $formatConfig);
                    if (!$parsed) continue;

                    $rate       = $this->resolveRate($parsed['called_number'], $tariffPlanId);
                    $record     = new CdrRecord($parsed);
                    $record->import_file_id = $importFile->id;

                    if ($rate) {
                        $cost = $this->calculateCost($record, $rate);
                        $record->called_prefix        = $rate->prefix;
                        $record->category             = $rate->category;
                        $record->rate_per_minute_cents = $rate->rate_per_minute_cents;
                        $record->connection_fee_cents  = $rate->connection_fee_cents;
                        $record->total_cost_cents      = $cost->getMinorAmount()->toInt();
                    }

                    $record->save();
                    $imported++;
                } catch (\Throwable $e) {
                    $failed++;
                    logger()->warning('CDR record parse failed', ['row' => $row, 'error' => $e->getMessage()]);
                }
            }

            $importFile->update([
                'status'           => 'completed',
                'records_imported' => $imported,
                'records_failed'   => $failed,
            ]);
        } catch (\Throwable $e) {
            $importFile->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }

        return $importFile;
    }

    /**
     * Finds the applicable rate using longest-prefix match.
     * Algorithm: sort rates by prefix length DESC, return first match.
     * Uses Redis cache for rate lookup.
     */
    public function resolveRate(string $calledNumber, int $tariffPlanId): ?CdrRate
    {
        $cacheKey = "prefix_rates:{$tariffPlanId}";
        $rates    = Cache::remember($cacheKey, config('cdr.rate_cache_ttl', 3600), function () use ($tariffPlanId) {
            return CdrRate::where('tariff_plan_id', $tariffPlanId)
                ->where('active', true)
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', now()->toDateString())
                ->get()
                ->sortByDesc(fn($r) => strlen($r->prefix))
                ->values();
        });

        foreach ($rates as $rate) {
            if (str_starts_with($calledNumber, $rate->prefix)) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Calculates cost for a single CDR record.
     * Formula: connection_fee + ceil(duration / billing_interval) * (rate_per_minute * interval / 60)
     */
    public function calculateCost(CdrRecord $record, CdrRate $rate): Money
    {
        $connectionFee   = Money::ofMinor($rate->connection_fee_cents, 'EUR');
        $intervals       = (int) ceil($record->duration_seconds / max(1, $rate->billing_interval_seconds));
        $intervalMinutes = $rate->billing_interval_seconds / 60;
        $costPerInterval = Money::ofMinor($rate->rate_per_minute_cents, 'EUR')
            ->multipliedBy($intervalMinutes, \Brick\Math\RoundingMode::HALF_UP);
        $callCost        = $costPerInterval->multipliedBy($intervals, \Brick\Math\RoundingMode::HALF_UP);

        return $connectionFee->plus($callCost);
    }

    private function parseRow(array $row, string $format, array $config): ?array
    {
        $dateFormat = $config['date_format'] ?? 'Y-m-d H:i:s';

        // Asterisk format: accountcode,src,dst,dcontext,clid,channel,dstchannel,lastapp,lastdata,start,answer,end,duration,billsec
        // Generic fallback: caller_number,called_number,duration_seconds,start_time
        $caller  = $row['src'] ?? $row['caller_number'] ?? $row[0] ?? null;
        $called  = $row['dst'] ?? $row['called_number'] ?? $row[1] ?? null;
        $duration = (int) ($row['billsec'] ?? $row['duration_seconds'] ?? $row[2] ?? 0);
        $startRaw = $row['start'] ?? $row['start_time'] ?? $row[3] ?? null;

        if (!$caller || !$called || !$startRaw) return null;

        $startTime = \Carbon\Carbon::createFromFormat($dateFormat, $startRaw);
        $endTime   = $startTime->copy()->addSeconds($duration);

        return [
            'caller_number'   => $caller,
            'called_number'   => $called,
            'duration_seconds'=> $duration,
            'start_time'      => $startTime,
            'end_time'        => $endTime,
        ];
    }

    private function detectFormat(string $content): string
    {
        $firstLine = strtolower(strtok($content, "\n"));
        if (str_contains($firstLine, 'accountcode') || str_contains($firstLine, 'billsec')) {
            return 'asterisk';
        }
        if (str_contains($firstLine, 'callid') || str_contains($firstLine, 'yeastar')) {
            return 'yeastar';
        }
        return 'generic';
    }
}
