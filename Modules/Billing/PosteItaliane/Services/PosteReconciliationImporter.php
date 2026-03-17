<?php

namespace Modules\Billing\PosteItaliane\Services;

use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Modules\Billing\PosteItaliane\Models\BollettinoTd896;
use Modules\Billing\PosteItaliane\Models\PosteReconciliationFile;

class PosteReconciliationImporter
{
    /**
     * Imports Poste reconciliation file.
     * Matches records to bollettini by numero_bollettino.
     * Updates BollettinoTd896 status to 'paid' and sets paid_at.
     */
    public function import(string $content, string $filename): PosteReconciliationFile
    {
        $reconciliation = PosteReconciliationFile::create([
            'filename'    => $filename,
            'imported_at' => now(),
            'raw_content' => $content,
        ]);

        $csv = Reader::createFromString($content);
        $csv->setHeaderOffset(0);
        $records = iterator_to_array($csv->getRecords());

        $total    = count($records);
        $matched  = 0;
        $unmatched = 0;

        DB::transaction(function () use ($records, $reconciliation, &$matched, &$unmatched) {
            foreach ($records as $row) {
                $numero = trim($row['numero_bollettino'] ?? $row[0] ?? '');

                $bollettino = BollettinoTd896::where('numero_bollettino', $numero)
                    ->lockForUpdate()
                    ->first();

                if ($bollettino) {
                    $bollettino->update([
                        'status'                  => 'paid',
                        'paid_at'                 => now(),
                        'reconciliation_file_id'  => $reconciliation->id,
                    ]);
                    $matched++;
                } else {
                    $unmatched++;
                }
            }
        });

        $reconciliation->update([
            'records_total'     => $total,
            'records_matched'   => $matched,
            'records_unmatched' => $unmatched,
        ]);

        return $reconciliation;
    }

    /**
     * Generates unique 18-digit Poste bollettino number.
     */
    public function generateNumeroBollettino(): string
    {
        $cc        = str_pad(config('poste_italiane.conto_corrente', '0'), 12, '0', STR_PAD_LEFT);
        $seq       = str_pad((string) (BollettinoTd896::count() + 1), 5, '0', STR_PAD_LEFT);
        $base      = $cc . $seq;
        $checksum  = $this->luhnCheckDigit($base);
        return $base . $checksum;
    }

    private function luhnCheckDigit(string $number): string
    {
        $sum    = 0;
        $flip   = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];
            if ($flip) {
                $digit *= 2;
                if ($digit > 9) $digit -= 9;
            }
            $sum += $digit;
            $flip = !$flip;
        }
        return (string) ((10 - ($sum % 10)) % 10);
    }
}
