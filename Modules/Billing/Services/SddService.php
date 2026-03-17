<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Enums\PaymentStatus;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\Payment;
use Modules\Billing\Models\SepaFile;
use Modules\Billing\Models\SepaMandate;

/**
 * Gestisce la generazione e il parsing dei file SEPA SDD (ISO 20022 CBISDDReqPhyMsg / pain.008).
 *
 * Flusso outbound:
 * 1. collectDueInvoices()  → raccoglie fatture da addebitare (D+5)
 * 2. generatePain008()     → costruisce XML pain.008
 * 3. store()               → salva su MinIO + crea record SepaFile
 *
 * Flusso inbound R-transaction (pain.002):
 * 4. processReturnFile()   → gestisce AC04, AM04, MD01, MS02...
 */
class SddService
{
    private bool $isMocked;

    public function __construct()
    {
        $this->isMocked = (bool) config('app.carrier_mock', false);
    }

    /**
     * Genera un file pain.008 per le fatture con metodo SDD in scadenza oggi.
     *
     * @param Collection<Invoice> $invoices
     * @return SepaFile
     */
    public function generateBatch(Collection $invoices): SepaFile
    {
        $settlementDate = Carbon::today()->addDays(5);
        $messageId      = $this->generateMessageId();

        if ($this->isMocked) {
            Log::info("[MOCK] SDD batch: {$invoices->count()} fatture, settlement {$settlementDate->toDateString()}");
        }

        // Eager-load mandates keyed by customer_id — avoids N+1 in loop + buildPain008
        $mandates = SepaMandate::whereIn('customer_id', $invoices->pluck('customer_id'))
            ->where('status', 'active')
            ->get()
            ->keyBy('customer_id');

        $controlSum = number_format($invoices->sum(fn(Invoice $i) => (float) $i->total), 2, '.', '');

        $xmlContent = $this->buildPain008($invoices, $messageId, $settlementDate, $mandates, $controlSum);

        $filename    = "pain008_{$messageId}_{$settlementDate->format('Ymd')}.xml";
        $storagePath = "sepa/{$settlementDate->format('Y/m')}/{$filename}";

        if (!$this->isMocked) {
            Storage::disk('minio-invoices')->put($storagePath, $xmlContent);
        }

        $sepaFile = SepaFile::create([
            'tenant_id'        => $invoices->first()->tenant_id,
            'message_id'       => $messageId,
            'type'             => 'pain008',
            'filename'         => $filename,
            'transaction_count' => $invoices->count(),
            'control_sum'      => $controlSum,
            'settlement_date'  => $settlementDate,
            'status'           => 'generated',
            'storage_path'     => $storagePath,
        ]);

        // Crea i record Payment (status=pending) collegati al file
        foreach ($invoices as $invoice) {
            $mandate = $mandates[$invoice->customer_id] ?? null;
            if (!$mandate) {
                Log::warning("SDD: nessun mandato attivo per customer #{$invoice->customer_id}");
                continue;
            }

            Payment::create([
                'tenant_id'          => $invoice->tenant_id,
                'invoice_id'         => $invoice->id,
                'customer_id'        => $invoice->customer_id,
                'method'             => 'sdd',
                'amount'             => $invoice->total,
                'currency'           => 'EUR',
                'status'             => PaymentStatus::Pending->value,
                'sepa_mandate_id'    => $mandate->mandate_id,
                'sepa_end_to_end_id' => $this->generateEndToEndId($invoice),
                'sepa_file_id'       => $sepaFile->id,
            ]);
        }

        return $sepaFile;
    }

    /**
     * Processa un file di ritorno R-transaction (pain.002 / CBISDDStsRptPhyMsg).
     *
     * @param string $xmlContent contenuto XML del file inbound
     * @return array{processed: int, errors: int}
     */
    public function processReturnFile(string $xmlContent): array
    {
        if ($this->isMocked) {
            Log::info('[MOCK] SDD processReturnFile chiamato');
            return ['processed' => 0, 'errors' => 0];
        }

        $xml = simplexml_load_string($xmlContent);
        $processed = 0;
        $errors = 0;

        // Namespace ISO 20022 CBI
        $ns = $xml->getNamespaces(true);

        foreach ($xml->xpath('//TxInfAndSts') as $tx) {
            $endToEndId   = (string) $tx->OrgnlEndToEndId;
            $returnCode   = (string) $tx->StsRsnInf->Rsn->Cd;

            $payment = Payment::where('sepa_end_to_end_id', $endToEndId)
                ->where('method', 'sdd')
                ->first();

            if (!$payment) {
                Log::warning("SDD R-transaction: EndToEndId {$endToEndId} non trovato");
                $errors++;
                continue;
            }

            $payment->update([
                'status'           => PaymentStatus::Failed->value,
                'sepa_return_code' => $returnCode,
                'processed_at'     => now(),
            ]);

            $this->handleReturnCode($payment, $returnCode);
            $processed++;
        }

        return compact('processed', 'errors');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Costruisce il body XML pain.008 (CBI ISO 20022).
     */
    private function buildPain008(Collection $invoices, string $messageId, Carbon $settlementDate, \Illuminate\Support\Collection $mandates, string $controlSum): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(
            'urn:CBI:xsd:CBISDDReqPhyMsg.00.01.00',
            'CBISDDReqPhyMsg'
        );
        $dom->appendChild($root);

        // CBIHdrTrt
        $hdrTrt = $dom->createElement('CBIHdrTrt');
        $hdrTrt->appendChild($dom->createElement('SdrIdCd', config('app.sdi_sender_code', 'ISPMANAGER')));
        $hdrTrt->appendChild($dom->createElement('RcvIdCd', config('app.cbi_receiver_code', 'CBISDI')));
        $hdrTrt->appendChild($dom->createElement('CreDtAndTm', now()->toIso8601String()));
        $root->appendChild($hdrTrt);

        // Document/CstmrDrctDbtInitn
        $doc = $dom->createElement('Document');
        $doc->setAttribute('xmlns', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');
        $root->appendChild($doc);

        $initn = $dom->createElement('CstmrDrctDbtInitn');
        $doc->appendChild($initn);

        // GrpHdr
        $grpHdr = $dom->createElement('GrpHdr');
        $grpHdr->appendChild($dom->createElement('MsgId', $messageId));
        $grpHdr->appendChild($dom->createElement('CreDtTm', now()->toIso8601String()));
        $grpHdr->appendChild($dom->createElement('NbOfTxs', (string) $invoices->count()));
        $grpHdr->appendChild($dom->createElement('CtrlSum', $controlSum));
        $initn->appendChild($grpHdr);

        // PmtInf
        $pmtInf = $dom->createElement('PmtInf');
        $pmtInf->appendChild($dom->createElement('PmtInfId', $messageId . '-PMT'));
        $pmtInf->appendChild($dom->createElement('PmtMtd', 'DD'));
        $pmtInf->appendChild($dom->createElement('NbOfTxs', (string) $invoices->count()));
        $pmtInf->appendChild($dom->createElement('CtrlSum', $controlSum));

        $pmtTpInf = $dom->createElement('PmtTpInf');
        $svcLvl = $dom->createElement('SvcLvl');
        $svcLvl->appendChild($dom->createElement('Cd', 'SEPA'));
        $pmtTpInf->appendChild($svcLvl);
        $lclInstrm = $dom->createElement('LclInstrm');
        $lclInstrm->appendChild($dom->createElement('Cd', 'CORE'));
        $pmtTpInf->appendChild($lclInstrm);
        $pmtTpInf->appendChild($dom->createElement('SeqTp', 'RCUR'));
        $pmtInf->appendChild($pmtTpInf);

        $pmtInf->appendChild($dom->createElement('ReqdColltnDt', $settlementDate->toDateString()));

        // Creditor (noi)
        $cdtr = $dom->createElement('Cdtr');
        $cdtr->appendChild($dom->createElement('Nm', config('app.company_ragione_sociale', '')));
        $pmtInf->appendChild($cdtr);

        $cdtrAcct = $dom->createElement('CdtrAcct');
        $id = $dom->createElement('Id');
        $id->appendChild($dom->createElement('IBAN', config('app.sepa_creditor_iban', '')));
        $cdtrAcct->appendChild($id);
        $pmtInf->appendChild($cdtrAcct);

        // Transactions
        foreach ($invoices as $invoice) {
            $mandate = $mandates[$invoice->customer_id] ?? null;
            if (!$mandate) {
                continue;
            }

            $txInf = $dom->createElement('DrctDbtTxInf');

            $pmtId = $dom->createElement('PmtId');
            $pmtId->appendChild($dom->createElement('EndToEndId', $this->generateEndToEndId($invoice)));
            $txInf->appendChild($pmtId);

            $instdAmt = $dom->createElement('InstdAmt', number_format((float)$invoice->total, 2, '.', ''));
            $instdAmt->setAttribute('Ccy', 'EUR');
            $txInf->appendChild($instdAmt);

            $drctDbtTx = $dom->createElement('DrctDbtTx');
            $mndtRltdInf = $dom->createElement('MndtRltdInf');
            $mndtRltdInf->appendChild($dom->createElement('MndtId', $mandate->mandate_id));
            $mndtRltdInf->appendChild($dom->createElement('DtOfSgntr', $mandate->signed_at->toDateString()));
            $drctDbtTx->appendChild($mndtRltdInf);
            $txInf->appendChild($drctDbtTx);

            $dbtr = $dom->createElement('Dbtr');
            $dbtr->appendChild($dom->createElement('Nm', $invoice->customer->full_name));
            $txInf->appendChild($dbtr);

            $dbtrAcct = $dom->createElement('DbtrAcct');
            $dbtrId = $dom->createElement('Id');
            $dbtrId->appendChild($dom->createElement('IBAN', $mandate->iban));
            $dbtrAcct->appendChild($dbtrId);
            $txInf->appendChild($dbtrAcct);

            $rmtInf = $dom->createElement('RmtInf');
            $rmtInf->appendChild($dom->createElement('Ustrd', "Fattura {$invoice->number}"));
            $txInf->appendChild($rmtInf);

            $pmtInf->appendChild($txInf);
        }

        $initn->appendChild($pmtInf);

        return $dom->saveXML();
    }

    /**
     * Gestisce i codici R-transaction secondo le specifiche CBI/SEPA.
     */
    private function handleReturnCode(Payment $payment, string $code): void
    {
        match($code) {
            'AC04' => $this->handleClosedAccount($payment),        // Conto chiuso
            'AM04' => $this->handleInsufficientFunds($payment),    // Fondi insufficienti → retry D+30
            'MD01' => $this->handleMandateNotFound($payment),      // Mandato non trovato
            'MS02' => $this->handleMandateRevoked($payment),       // Cliente ha revocato
            'MD06' => $this->handleRefundRequest($payment),        // Richiesta rimborso
            default => Log::warning("SDD: codice R-transaction sconosciuto: {$code} per payment #{$payment->id}"),
        };
    }

    private function handleClosedAccount(Payment $payment): void
    {
        Log::warning("SDD AC04: conto chiuso per customer #{$payment->customer_id} — cessare mandato");
        $this->revokeActiveMandates($payment->customer_id, 'AC04');
    }

    private function handleInsufficientFunds(Payment $payment): void
    {
        Log::info("SDD AM04: fondi insufficienti per invoice #{$payment->invoice_id} — secondo tentativo schedulato D+30");
        // Il DunningService gestirà il retry via DunningAction::RetrySdd al giorno 30
    }

    private function handleMandateNotFound(Payment $payment): void
    {
        Log::warning("SDD MD01: mandato non trovato per payment #{$payment->id}");
    }

    private function handleMandateRevoked(Payment $payment): void
    {
        Log::warning("SDD MS02: mandato revocato dal cliente #{$payment->customer_id}");
        $this->revokeActiveMandates($payment->customer_id, 'MS02');
    }

    private function revokeActiveMandates(int $customerId, string $code): void
    {
        SepaMandate::where('customer_id', $customerId)
            ->active()
            ->each(fn(SepaMandate $m) => $m->revoke($code));
    }

    private function handleRefundRequest(Payment $payment): void
    {
        Log::warning("SDD MD06: richiesta rimborso per invoice #{$payment->invoice_id} — aprire contestazione");
    }

    private function generateMessageId(): string
    {
        return 'ISP' . now()->format('YmdHis') . strtoupper(substr(uniqid(), -6));
    }

    private function generateEndToEndId(Invoice $invoice): string
    {
        return 'E2E-' . $invoice->id . '-' . now()->format('YmdHis');
    }
}
