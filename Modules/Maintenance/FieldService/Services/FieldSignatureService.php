<?php

namespace Modules\Maintenance\FieldService\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Maintenance\FieldService\Enums\SignerType;
use Modules\Maintenance\FieldService\Models\FieldIntervention;
use Modules\Maintenance\FieldService\Models\FieldSignature;

class FieldSignatureService
{
    /**
     * Sends OTP for FEA signature verification.
     */
    public function sendOtp(FieldIntervention $intervention, string $phone): string
    {
        $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(config('field_service.otp_expires_minutes', 10));
        $cacheKey = "field:otp:{$intervention->uuid}";

        Cache::put($cacheKey, ['otp' => $otp, 'expires_at' => $expires->toIso8601String()], $expires);

        if (!config('app.carrier_mock', false)) {
            // Real SMS via Twilio or similar
            // app(SmsService::class)->send($phone, "Il tuo codice OTP per firma verbale: {$otp}");
        }

        // In mock mode return OTP directly; in prod return empty string
        return config('app.carrier_mock', false) ? $otp : '';
    }

    /**
     * Verifies OTP and stores canvas PNG signature.
     */
    public function verifyAndSign(
        FieldIntervention $intervention,
        string $otp,
        string $signaturePngBase64,
        string $signerName,
        SignerType $signerType
    ): FieldSignature {
        $cacheKey = "field:otp:{$intervention->uuid}";
        $stored   = Cache::get($cacheKey);

        if (!$stored || $stored['otp'] !== $otp) {
            throw new \InvalidArgumentException('OTP non valido o scaduto.');
        }

        Cache::forget($cacheKey);

        $path = $this->storeSignaturePng($intervention, $signaturePngBase64);

        return FieldSignature::create([
            'intervention_id'  => $intervention->id,
            'signer_type'      => $signerType->value,
            'signer_name'      => $signerName,
            'signature_path'   => $path,
            'otp_code'         => hash('sha256', $otp),
            'otp_verified_at'  => now(),
            'signed_at'        => now(),
        ]);
    }

    /**
     * Saves technician signature (no OTP required).
     */
    public function saveTechnicianSignature(FieldIntervention $intervention, string $signaturePngBase64): FieldSignature
    {
        $path = $this->storeSignaturePng($intervention, $signaturePngBase64);

        return FieldSignature::create([
            'intervention_id'  => $intervention->id,
            'signer_type'      => SignerType::Technician->value,
            'signer_name'      => 'Tecnico',
            'signature_path'   => $path,
            'signed_at'        => now(),
        ]);
    }

    private function storeSignaturePng(FieldIntervention $intervention, string $base64): string
    {
        $pngData = base64_decode(preg_replace('/^data:image\/png;base64,/', '', $base64));
        $path    = config('field_service.signatures_storage_path') . "/{$intervention->uuid}_" . uniqid() . '.png';
        Storage::disk(config('field_service.signatures_storage_disk', 'minio'))->put($path, $pngData);
        return $path;
    }
}
