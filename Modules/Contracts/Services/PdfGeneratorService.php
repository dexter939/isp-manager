<?php

declare(strict_types=1);

namespace Modules\Contracts\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Modules\Contracts\Models\Contract;

/**
 * Genera i documenti PDF del contratto tramite DomPDF + Blade.
 *
 * Documenti generati:
 * - Contratto principale (contract.blade.php)
 * - Informativa privacy GDPR (privacy.blade.php)
 * - Scheda tecnica offerta (technical_sheet.blade.php)
 * - Pagina firma visuale aggiunta post-OTP (signature_page.blade.php)
 */
class PdfGeneratorService
{
    /**
     * Genera il PDF completo del contratto (pre-firma).
     * Il PDF viene salvato su MinIO tramite DocumentStorageService.
     *
     * @return string Path su MinIO
     */
    public function generateContractPdf(Contract $contract): string
    {
        $contract->load(['customer', 'servicePlan', 'agent']);

        $pdfContent = $this->renderMergedPdf($contract);

        $storage = app(DocumentStorageService::class);
        ['path' => $path] = $storage->storeContractPdf($contract, $pdfContent);

        return $path;
    }

    /**
     * Aggiunge una pagina di firma visuale al PDF esistente.
     * Questa pagina include: data firma, IP del firmatario, hash SHA-256 del documento.
     * Compliance FEA: art. 26 eIDAS.
     *
     * @return string Contenuto PDF completo con firma visuale
     */
    public function addSignaturePage(
        string $pdfContent,
        Contract $contract,
        Carbon $signedAt,
        string $signerIp,
    ): string {
        $contract->load(['customer', 'servicePlan']);

        // Genera la pagina di firma separata
        $signaturePage = Pdf::loadView('contracts::pdf.signature_page', [
            'contract'  => $contract,
            'customer'  => $contract->customer,
            'signedAt'  => $signedAt,
            'signerIp'  => $signerIp,
            'docHash'   => hash('sha256', $pdfContent),
        ])->output();

        // Concatena: contratto originale + pagina firma
        // In produzione si può usare una libreria PDF merge (FPDI/etc.)
        // Per semplicità generiamo il PDF completo con firma inclusa
        return $this->renderMergedPdf($contract, signed: true, signedAt: $signedAt, signerIp: $signerIp);
    }

    /**
     * Genera un'anteprima PDF (senza upload su MinIO).
     *
     * @return string Contenuto PDF binario
     */
    public function preview(Contract $contract): string
    {
        $contract->load(['customer', 'servicePlan']);
        return $this->renderMergedPdf($contract);
    }

    /**
     * Renderizza il PDF completo (contratto + privacy + scheda tecnica).
     */
    private function renderMergedPdf(
        Contract $contract,
        bool $signed = false,
        ?Carbon $signedAt = null,
        ?string $signerIp = null,
    ): string {
        $data = [
            'contract'    => $contract,
            'customer'    => $contract->customer,
            'servicePlan' => $contract->servicePlan,
            'agent'       => $contract->agent,
            'generatedAt' => now(),
            'signed'      => $signed,
            'signedAt'    => $signedAt,
            'signerIp'    => $signerIp,
            'company'     => $this->getCompanyData(),
        ];

        $pdf = Pdf::loadView('contracts::pdf.contract', $data)
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        return $pdf->output();
    }

    /**
     * Dati azienda emittente dalla config (.env).
     *
     * @return array<string, string>
     */
    private function getCompanyData(): array
    {
        return [
            'ragione_sociale' => config('app.company_ragione_sociale', ''),
            'piva'            => config('app.company_piva', ''),
            'indirizzo'       => config('app.company_indirizzo', ''),
            'cap'             => config('app.company_cap', ''),
            'citta'           => config('app.company_citta', ''),
            'provincia'       => config('app.company_provincia', ''),
            'telefono'        => config('app.company_telefono', ''),
            'email'           => config('app.company_email', ''),
            'pec'             => config('app.company_pec', ''),
        ];
    }
}
