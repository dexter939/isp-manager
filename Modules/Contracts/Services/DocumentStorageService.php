<?php

declare(strict_types=1);

namespace Modules\Contracts\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Models\CustomerDocument;

/**
 * Gestisce l'upload e il download sicuro di documenti su MinIO.
 * Tutti i documenti contrattuali vanno nel bucket 'contracts' (WORM).
 * Le fatture vanno nel bucket 'invoices'.
 */
class DocumentStorageService
{
    private const DISK = 's3';
    private const BUCKET_CONTRACTS = 'ispmanager-contracts';
    private const BUCKET_INVOICES  = 'ispmanager-invoices';

    /**
     * Salva il PDF del contratto firmato su MinIO (bucket WORM).
     * Calcola SHA-256 per verifica integrità.
     *
     * @return array{path: string, sha256: string}
     */
    public function storeContractPdf(Contract $contract, string $pdfContent): array
    {
        $path = sprintf(
            'contracts/%d/%d/%s_contratto_%s.pdf',
            now()->year,
            now()->month,
            $contract->id,
            now()->format('YmdHis')
        );

        Storage::disk(self::DISK)->put($path, $pdfContent, [
            'ContentType'        => 'application/pdf',
            'ContentDisposition' => "attachment; filename=\"contratto_{$contract->id}.pdf\"",
        ]);

        $sha256 = hash('sha256', $pdfContent);

        // Crea record in customer_documents
        CustomerDocument::create([
            'customer_id' => $contract->customer_id,
            'contract_id' => $contract->id,
            'type'        => 'contract',
            'name'        => "contratto_{$contract->id}.pdf",
            'disk'        => self::DISK,
            'path'        => $path,
            'mime_type'   => 'application/pdf',
            'size_bytes'  => strlen($pdfContent),
            'sha256'      => $sha256,
            'is_signed'   => false,
        ]);

        return ['path' => $path, 'sha256' => $sha256];
    }

    /**
     * Aggiorna il PDF con la versione firmata (post-firma FEA).
     * Sovrascrive il file pre-firma e marca il documento come firmato.
     *
     * @return array{path: string, sha256: string}
     */
    public function replaceWithSignedPdf(Contract $contract, string $signedPdfContent): array
    {
        $path = $contract->pdf_path;

        // Overwrite con versione firmata
        Storage::disk(self::DISK)->put($path, $signedPdfContent, [
            'ContentType' => 'application/pdf',
        ]);

        $sha256 = hash('sha256', $signedPdfContent);

        // Aggiorna record documento
        CustomerDocument::where('contract_id', $contract->id)
            ->where('type', 'contract')
            ->update([
                'sha256'      => $sha256,
                'size_bytes'  => strlen($signedPdfContent),
                'is_signed'   => true,
                'signed_at'   => now(),
                'updated_at'  => now(),
            ]);

        return ['path' => $path, 'sha256' => $sha256];
    }

    /**
     * Genera URL temporaneo firmato per download sicuro (default 5 minuti).
     */
    public function temporaryDownloadUrl(string $path, int $minutes = 5): string
    {
        return Storage::disk(self::DISK)->temporaryUrl($path, now()->addMinutes($minutes));
    }

    /**
     * Verifica l'integrità di un documento confrontando l'hash SHA-256.
     */
    public function verifyIntegrity(string $path, string $expectedSha256): bool
    {
        $content = Storage::disk(self::DISK)->get($path);
        return hash('sha256', $content) === $expectedSha256;
    }

    /**
     * Recupera il contenuto di un documento (per generazione PDF firmato).
     */
    public function getContent(string $path): string
    {
        return Storage::disk(self::DISK)->get($path);
    }
}
