<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Services;

use DOMDocument;
use DOMElement;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Sdi\Exceptions\SdiValidationException;

class FatturaXmlGenerator
{
    private int $progressivoInvio = 1;

    /**
     * Generates a complete FatturaPA 1.2 XML string for the given invoice.
     *
     * Includes: DatiTrasmissione, CedentePrestatore, CessionarioCommittente,
     * DatiBeniServizi, DatiPagamento.
     *
     * @param  Invoice $invoice The invoice to generate XML for.
     * @return string           The complete FatturaPA 1.2 XML string.
     *
     * @throws SdiValidationException If XSD validation is enabled and the XML is invalid.
     * @throws SdiValidationException If required invoice data is missing.
     */
    public function generate(Invoice $invoice): string
    {
        $cedente = config('sdi.cedente');

        if (empty($cedente['partita_iva'])) {
            throw SdiValidationException::missingInvoiceData('sdi.cedente.partita_iva');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Root element
        $root = $dom->createElementNS(
            'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2',
            'p:FatturaElettronica'
        );
        $root->setAttribute('versione', 'FPR12');
        $root->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $dom->appendChild($root);

        // ── FatturaElettronicaHeader ──────────────────────────────────────────
        $header = $dom->createElement('FatturaElettronicaHeader');
        $root->appendChild($header);

        // DatiTrasmissione
        $datiTrasmissione = $dom->createElement('DatiTrasmissione');
        $header->appendChild($datiTrasmissione);

        $idTrasmittente = $dom->createElement('IdTrasmittente');
        $datiTrasmissione->appendChild($idTrasmittente);
        $this->appendElement($dom, $idTrasmittente, 'IdPaese', 'IT');
        $this->appendElement($dom, $idTrasmittente, 'IdCodice', $cedente['partita_iva']);

        $this->appendElement($dom, $datiTrasmissione, 'ProgressivoInvio', $this->padProgressivo($invoice));
        $this->appendElement($dom, $datiTrasmissione, 'FormatoTrasmissione', 'FPR12');
        $this->appendElement($dom, $datiTrasmissione, 'CodiceDestinatario', $this->getCodiceDestinatario($invoice));

        // CedentePrestatore
        $cedentePrestatore = $dom->createElement('CedentePrestatore');
        $header->appendChild($cedentePrestatore);

        $datiAnagraficiCedente = $dom->createElement('DatiAnagrafici');
        $cedentePrestatore->appendChild($datiAnagraficiCedente);

        $idFiscaleIvaCedente = $dom->createElement('IdFiscaleIVA');
        $datiAnagraficiCedente->appendChild($idFiscaleIvaCedente);
        $this->appendElement($dom, $idFiscaleIvaCedente, 'IdPaese', 'IT');
        $this->appendElement($dom, $idFiscaleIvaCedente, 'IdCodice', $cedente['partita_iva']);

        if (!empty($cedente['codice_fiscale'])) {
            $this->appendElement($dom, $datiAnagraficiCedente, 'CodiceFiscale', $cedente['codice_fiscale']);
        }

        $anagraficaCedente = $dom->createElement('Anagrafica');
        $datiAnagraficiCedente->appendChild($anagraficaCedente);
        $this->appendElement($dom, $anagraficaCedente, 'Denominazione', $cedente['denominazione']);

        $this->appendElement($dom, $datiAnagraficiCedente, 'RegimeFiscale', $cedente['regime_fiscale']);

        $sedeCedente = $dom->createElement('Sede');
        $cedentePrestatore->appendChild($sedeCedente);
        $this->appendElement($dom, $sedeCedente, 'Indirizzo', $cedente['indirizzo']);
        $this->appendElement($dom, $sedeCedente, 'CAP', $cedente['cap']);
        $this->appendElement($dom, $sedeCedente, 'Comune', $cedente['comune']);
        if (!empty($cedente['provincia'])) {
            $this->appendElement($dom, $sedeCedente, 'Provincia', $cedente['provincia']);
        }
        $this->appendElement($dom, $sedeCedente, 'Nazione', $cedente['nazione'] ?? 'IT');

        // CessionarioCommittente
        $cessionario = $dom->createElement('CessionarioCommittente');
        $header->appendChild($cessionario);

        $datiAnagraficiCessionario = $dom->createElement('DatiAnagrafici');
        $cessionario->appendChild($datiAnagraficiCessionario);

        $customer = $invoice->customer;
        if ($customer && !empty($customer->partita_iva)) {
            $idFiscaleIvaCessionario = $dom->createElement('IdFiscaleIVA');
            $datiAnagraficiCessionario->appendChild($idFiscaleIvaCessionario);
            $this->appendElement($dom, $idFiscaleIvaCessionario, 'IdPaese', 'IT');
            $this->appendElement($dom, $idFiscaleIvaCessionario, 'IdCodice', $customer->partita_iva);
        }
        if ($customer && !empty($customer->codice_fiscale)) {
            $this->appendElement($dom, $datiAnagraficiCessionario, 'CodiceFiscale', $customer->codice_fiscale);
        }

        $anagraficaCessionario = $dom->createElement('Anagrafica');
        $datiAnagraficiCessionario->appendChild($anagraficaCessionario);
        $customerName = $customer ? ($customer->company_name ?? $customer->full_name ?? 'Cliente') : 'Cliente';
        $this->appendElement($dom, $anagraficaCessionario, 'Denominazione', $customerName);

        $sedeCessionario = $dom->createElement('Sede');
        $cessionario->appendChild($sedeCessionario);
        $this->appendElement($dom, $sedeCessionario, 'Indirizzo', $customer->address ?? 'N/D');
        $this->appendElement($dom, $sedeCessionario, 'CAP', $customer->cap ?? '00000');
        $this->appendElement($dom, $sedeCessionario, 'Comune', $customer->city ?? 'N/D');
        $this->appendElement($dom, $sedeCessionario, 'Nazione', 'IT');

        // ── FatturaElettronicaBody ────────────────────────────────────────────
        $body = $dom->createElement('FatturaElettronicaBody');
        $root->appendChild($body);

        // DatiGenerali
        $datiGenerali = $dom->createElement('DatiGenerali');
        $body->appendChild($datiGenerali);

        $datiGeneraliDocumento = $dom->createElement('DatiGeneraliDocumento');
        $datiGenerali->appendChild($datiGeneraliDocumento);

        $this->appendElement($dom, $datiGeneraliDocumento, 'TipoDocumento', 'TD01');
        $this->appendElement($dom, $datiGeneraliDocumento, 'Divisa', 'EUR');
        $this->appendElement($dom, $datiGeneraliDocumento, 'Data', $invoice->issue_date->format('Y-m-d'));
        $this->appendElement($dom, $datiGeneraliDocumento, 'Numero', (string) $invoice->number);

        $importoTotale = number_format((float) $invoice->total, 2, '.', '');
        $this->appendElement($dom, $datiGeneraliDocumento, 'ImportoTotaleDocumento', $importoTotale);

        // DatiBeniServizi
        $datiBeniServizi = $dom->createElement('DatiBeniServizi');
        $body->appendChild($datiBeniServizi);

        $items = $invoice->items ?? collect();
        $lineNumber = 1;

        foreach ($items as $item) {
            $dettaglioLinea = $dom->createElement('DettaglioLinee');
            $datiBeniServizi->appendChild($dettaglioLinea);

            $this->appendElement($dom, $dettaglioLinea, 'NumeroLinea', (string) $lineNumber++);
            $this->appendElement($dom, $dettaglioLinea, 'Descrizione', mb_substr((string) ($item->description ?? 'Servizio'), 0, 1000));
            $this->appendElement($dom, $dettaglioLinea, 'Quantita', number_format((float) ($item->quantity ?? 1), 2, '.', ''));
            $this->appendElement($dom, $dettaglioLinea, 'UnitaMisura', 'PZ');
            $this->appendElement($dom, $dettaglioLinea, 'PrezzoUnitario', number_format((float) ($item->unit_price ?? $item->total ?? 0), 8, '.', ''));
            $this->appendElement($dom, $dettaglioLinea, 'PrezzoTotale', number_format((float) ($item->total ?? 0), 2, '.', ''));
            $this->appendElement($dom, $dettaglioLinea, 'AliquotaIVA', number_format((float) ($invoice->tax_rate ?? 22), 2, '.', ''));
        }

        // DatiRiepilogo
        $datiRiepilogo = $dom->createElement('DatiRiepilogo');
        $datiBeniServizi->appendChild($datiRiepilogo);

        $this->appendElement($dom, $datiRiepilogo, 'AliquotaIVA', number_format((float) ($invoice->tax_rate ?? 22), 2, '.', ''));
        $this->appendElement($dom, $datiRiepilogo, 'ImponibileImporto', number_format((float) ($invoice->subtotal ?? 0), 2, '.', ''));
        $this->appendElement($dom, $datiRiepilogo, 'Imposta', number_format((float) ($invoice->tax_amount ?? 0), 2, '.', ''));
        $this->appendElement($dom, $datiRiepilogo, 'EsigibilitaIVA', 'I');

        // DatiPagamento
        $datiPagamento = $dom->createElement('DatiPagamento');
        $body->appendChild($datiPagamento);

        $this->appendElement($dom, $datiPagamento, 'CondizioniPagamento', 'TP02');

        $dettaglioPagamento = $dom->createElement('DettaglioPagamento');
        $datiPagamento->appendChild($dettaglioPagamento);

        $this->appendElement($dom, $dettaglioPagamento, 'ModalitaPagamento', $this->getModalitaPagamento($invoice));
        $this->appendElement($dom, $dettaglioPagamento, 'DataScadenzaPagamento', $invoice->due_date?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d'));
        $this->appendElement($dom, $dettaglioPagamento, 'ImportoPagamento', $importoTotale);

        $xmlString = $dom->saveXML();

        if (config('sdi.validate_xsd', true)) {
            $this->validateXsd($xmlString);
        }

        return $xmlString;
    }

    /**
     * Generates the SDI filename for a FatturaPA transmission.
     *
     * @param  Invoice $invoice The invoice to generate the filename for.
     * @return string           e.g. IT12345678901_FPR12.xml
     */
    public function generateFilename(Invoice $invoice): string
    {
        $partitaIva = config('sdi.cedente.partita_iva', '00000000000');
        $progressive = str_pad((string) ($invoice->sdi_progressive ?? $invoice->id), 5, '0', STR_PAD_LEFT);

        return "IT{$partitaIva}_FPR{$progressive}.xml";
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function appendElement(DOMDocument $dom, DOMElement $parent, string $name, string $value): DOMElement
    {
        $element = $dom->createElement($name, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
        $parent->appendChild($element);

        return $element;
    }

    private function padProgressivo(Invoice $invoice): string
    {
        return str_pad((string) ($invoice->sdi_progressive ?? $invoice->id), 5, '0', STR_PAD_LEFT);
    }

    private function getCodiceDestinatario(Invoice $invoice): string
    {
        $customer = $invoice->customer;

        if ($customer && !empty($customer->sdi_codice_destinatario)) {
            return $customer->sdi_codice_destinatario;
        }

        // Default to "0000000" for private individuals (use PEC instead)
        return '0000000';
    }

    private function getModalitaPagamento(Invoice $invoice): string
    {
        return match ((string) ($invoice->payment_method ?? '')) {
            'sepa', 'sepa_sdd' => 'MP19', // SEPA Direct Debit
            'bank_transfer'    => 'MP05', // Bonifico
            'stripe', 'card'   => 'MP08', // Carta di credito
            default            => 'MP05', // Default: Bonifico
        };
    }

    /**
     * Validates the generated XML against the FatturaPA 1.2 XSD.
     *
     * @throws SdiValidationException If the XML fails validation.
     */
    private function validateXsd(string $xmlString): void
    {
        $xsdPath = module_path('Sdi', 'Resources/xsd/Schema_VFPR12.xsd');

        if (! file_exists($xsdPath)) {
            // XSD not available — skip validation
            return;
        }

        $dom = new DOMDocument();
        $dom->loadXML($xmlString);

        libxml_use_internal_errors(true);
        $valid = $dom->schemaValidate($xsdPath);

        if (! $valid) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(fn ($e) => trim($e->message), $errors);
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            throw SdiValidationException::xsdValidationFailed(implode('; ', $errorMessages));
        }

        libxml_use_internal_errors(false);
    }
}
