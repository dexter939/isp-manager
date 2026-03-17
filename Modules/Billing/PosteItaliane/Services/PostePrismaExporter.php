<?php

namespace Modules\Billing\PosteItaliane\Services;

use Illuminate\Support\Collection;
use League\Csv\Writer;
use Modules\Billing\PosteItaliane\Models\BollettinoTd896;

class PostePrismaExporter
{
    /**
     * Exports bollettini to Poste Prisma CSV format for batch submission.
     */
    public function export(Collection $bollettini): string
    {
        $csv = Writer::createFromString('');
        $csv->insertOne(['numero_bollettino', 'importo', 'causale', 'conto_corrente', 'scadenza']);

        foreach ($bollettini as $bollettino) {
            $csv->insertOne([
                $bollettino->numero_bollettino,
                number_format($bollettino->importo_centesimi / 100, 2, '.', ''),
                $bollettino->causale,
                $bollettino->conto_corrente,
                $bollettino->scadenza_at->format('d/m/Y'),
            ]);
        }

        return $csv->toString();
    }
}
