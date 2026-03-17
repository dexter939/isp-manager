<?php

declare(strict_types=1);

namespace Modules\Contracts\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Contracts\Data\SignatureResult;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\ContractSignature;

/**
 * Firma Elettronica Avanzata (FEA) tramite OTP.
 *
 * Compliance: art. 26 eIDAS, delibere AgID.
 * Flow:
 *   1. sendOtp()     → genera OTP, SHA-256 PDF pre-firma, invia via SMS/WhatsApp
 *   2. verifyAndSign() → verifica OTP, aggiunge firma visuale PDF, SHA-256 post-firma
 *
 * Log conservato 10 anni in contract_signatures (audit trail legale).
 */
class FEAService
{
    private const OTP_LENGTH      = 6;
    private const OTP_TTL_HOURS   = 24;
    private const MAX_ATTEMPTS    = 3;

    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly DocumentStorageService $storage,
        private readonly NotificationService $notifier,
    ) {}

    /**
     * Genera e invia OTP al cliente per firma FEA.
     * Calcola SHA-256 del PDF pre-firma per audit trail.
     *
     * @throws \RuntimeException se il contratto non ha ancora un PDF
     */
    public function sendOtp(Contract $contract, string $channel = 'sms'): ContractSignature
    {
        if (!$contract->pdf_path) {
            throw new \RuntimeException("Contratto #{$contract->id}: PDF non ancora generato. Chiamare sendForSignature() prima.");
        }

        // Invalida eventuali OTP precedenti non usati
        ContractSignature::where('contract_id', $contract->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        // Genera OTP
        $otp    = $this->generateOtp();
        $sentTo = $this->getMaskedRecipient($contract, $channel);

        // SHA-256 del PDF pre-firma (prima di aggiungere la firma visuale)
        $pdfContent     = $this->storage->getContent($contract->pdf_path);
        $hashPreFirma   = hash('sha256', $pdfContent);

        // Salva record firma
        $signature = ContractSignature::create([
            'contract_id'      => $contract->id,
            'otp_hash'         => Hash::make($otp),
            'otp_channel'      => $channel,
            'otp_sent_to'      => $sentTo,
            'otp_sent_at'      => now(),
            'otp_expires_at'   => now()->addHours(self::OTP_TTL_HOURS),
            'pdf_hash_pre_firma' => $hashPreFirma,
            'status'           => 'pending',
        ]);

        // Invia OTP via SMS o WhatsApp
        $this->notifier->sendOtp($contract->customer, $otp, $channel);

        return $signature;
    }

    /**
     * Verifica OTP e completa la firma FEA.
     * Aggiunge firma visuale al PDF, calcola SHA-256 post-firma,
     * salva su MinIO, aggiorna contratto.
     *
     * @throws \InvalidArgumentException se OTP non valido / scaduto / tentativi esauriti
     */
    public function verifyAndSign(
        Contract $contract,
        string $otp,
        string $clientIp,
        string $userAgent,
    ): SignatureResult {
        $signature = ContractSignature::where('contract_id', $contract->id)
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        // Controlla scadenza
        if ($signature->isExpired()) {
            $signature->update(['status' => 'expired']);
            throw new \InvalidArgumentException('OTP scaduto. Richiedere un nuovo codice.');
        }

        // Controlla tentativi
        if ($signature->failed_attempts >= self::MAX_ATTEMPTS) {
            $signature->update(['status' => 'failed', 'failure_reason' => 'Troppi tentativi falliti']);
            throw new \InvalidArgumentException('Numero massimo di tentativi superato. Richiedere un nuovo codice.');
        }

        // Verifica OTP
        if (!Hash::check($otp, $signature->otp_hash)) {
            $signature->increment('failed_attempts');
            $remaining = self::MAX_ATTEMPTS - $signature->fresh()->failed_attempts;
            throw new \InvalidArgumentException("OTP non corretto. Tentativi rimanenti: {$remaining}.");
        }

        // OTP verificato — aggiungi firma visuale al PDF
        $signedPdfContent = $this->pdfGenerator->addSignaturePage(
            pdfContent: $this->storage->getContent($contract->pdf_path),
            contract: $contract,
            signedAt: now(),
            signerIp: $clientIp,
        );

        // Upload PDF firmato su MinIO (WORM)
        ['path' => $path, 'sha256' => $hashPostFirma] = $this->storage->replaceWithSignedPdf(
            contract: $contract,
            signedPdfContent: $signedPdfContent,
        );

        // Aggiorna record firma
        $signature->update([
            'otp_verified_at'     => now(),
            'otp_used'            => true,
            'signer_ip'           => $clientIp,
            'signer_user_agent'   => $userAgent,
            'signed_at'           => now(),
            'pdf_hash_post_firma' => $hashPostFirma,
            'status'              => 'signed',
        ]);

        // Attiva il contratto
        app(ContractService::class)->activate($contract, $clientIp, $hashPostFirma);

        return new SignatureResult(
            success: true,
            contractId: $contract->id,
            signatureId: $signature->id,
            signedAt: now(),
            pdfHashSha256: $hashPostFirma,
        );
    }

    /** Genera OTP numerico a 6 cifre crittograficamente sicuro */
    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /** Restituisce contatto mascherato (es: +39 333 ***4567) */
    private function getMaskedRecipient(Contract $contract, string $channel): string
    {
        $customer = $contract->customer;
        $contact  = match ($channel) {
            'whatsapp', 'sms' => $customer->cellulare ?? $customer->telefono ?? '',
            'email'           => $customer->email ?? '',
            default           => '',
        };

        if (strlen($contact) > 4) {
            return substr($contact, 0, -4) . '****';
        }

        return '****';
    }
}
