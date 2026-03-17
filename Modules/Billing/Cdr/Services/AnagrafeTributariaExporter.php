<?php

namespace Modules\Billing\Cdr\Services;

use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Cdr\Models\AnagrafeTributariaExport;
use Modules\Billing\Cdr\Models\CdrRecord;

class AnagrafeTributariaExporter
{
    /**
     * Generates XML export per D.Lgs. 196/2003 for Anagrafe Tributaria.
     * Stores to MinIO bucket 'documents'.
     */
    public function export(int $year): AnagrafeTributariaExport
    {
        $records = CdrRecord::whereYear('start_time', $year)
            ->where('billed', true)
            ->with('importFile')
            ->get();

        $xmlString = $this->buildXml($year, $records);
        $path      = "anagrafe/{$year}_comunicazione_dati_telefonia.xml";

        Storage::disk('minio')->put("documents/{$path}", $xmlString);

        $export = AnagrafeTributariaExport::create([
            'period_year'       => $year,
            'export_type'       => 'full',
            'total_records'     => $records->count(),
            'total_amount_cents'=> $records->sum('total_cost_cents'),
            'xml_path'          => "documents/{$path}",
            'generated_at'      => now(),
        ]);

        return $export;
    }

    /**
     * Builds Anagrafe Tributaria XML for VoIP call records.
     */
    private function buildXml(int $year, \Illuminate\Support\Collection $records): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('ComunicazioneDatiTelefonia');
        $root->setAttribute('anno', (string) $year);
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $dom->appendChild($root);

        $fornitore = $dom->createElement('Fornitore');
        $fornitore->appendChild($dom->createElement('PartitaIVA', config('sdi.cedente.partita_iva', '')));
        $fornitore->appendChild($dom->createElement('Denominazione', config('sdi.cedente.denominazione', '')));
        $root->appendChild($fornitore);

        $chiamate = $dom->createElement('Chiamate');
        foreach ($records as $record) {
            $chiamata = $dom->createElement('Chiamata');
            $chiamata->appendChild($dom->createElement('NumeroChiamante', $record->caller_number));
            $chiamata->appendChild($dom->createElement('NumeroChiamato', $record->called_number));
            $chiamata->appendChild($dom->createElement('DataOra', $record->start_time?->format('Y-m-d\TH:i:s') ?? ''));
            $chiamata->appendChild($dom->createElement('DurataSecondi', (string) $record->duration_seconds));
            $chiamata->appendChild($dom->createElement('Costo', number_format(($record->total_cost_cents ?? 0) / 100, 2, '.', '')));
            $chiamate->appendChild($chiamata);
        }
        $root->appendChild($chiamate);

        return $dom->saveXML();
    }
}
