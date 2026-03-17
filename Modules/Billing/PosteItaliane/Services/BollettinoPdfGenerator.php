<?php

namespace Modules\Billing\PosteItaliane\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\PosteItaliane\Models\BollettinoTd896;

class BollettinoPdfGenerator
{
    /**
     * Generates a bollettino postale TD896 PDF.
     * Returns PDF binary string and stores to MinIO.
     */
    public function generate(BollettinoTd896 $bollettino): string
    {
        $data = [
            'bollettino'      => $bollettino,
            'importo'         => $bollettino->getImportoAttribute()->getAmount()->toFloat(),
            'intestatario'    => config('poste_italiane.intestatario'),
            'conto_corrente'  => $bollettino->conto_corrente,
        ];

        $pdf    = Pdf::loadView('poste_italiane::bollettino_td896', $data)
            ->setPaper('A4', 'portrait');
        $output = $pdf->output();

        Storage::disk('minio')->put("invoices/{$bollettino->uuid}.pdf", $output);

        return $output;
    }

    /**
     * Generates merged PDF for a batch of bollettini (one per page).
     */
    public function generateBatch(\Illuminate\Support\Collection $bollettini): string
    {
        $outputs = $bollettini->map(fn($b) => $this->generate($b))->implode('');
        return $outputs;
    }
}
