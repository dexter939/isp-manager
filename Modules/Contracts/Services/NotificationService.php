<?php

declare(strict_types=1);

namespace Modules\Contracts\Services;

use Illuminate\Support\Facades\Log;
use Modules\Contracts\Models\Customer;

/**
 * Invia notifiche ai clienti (OTP, conferma firma, attivazione).
 * Usa Twilio (SMS) e Meta WABA (WhatsApp).
 * In ambiente CARRIER_MOCK=true logga invece di inviare.
 */
class NotificationService
{
    private bool $isMocked;

    public function __construct()
    {
        $this->isMocked = (bool) config('app.carrier_mock', false);
    }

    /**
     * Invia OTP via SMS o WhatsApp.
     */
    public function sendOtp(Customer $customer, string $otp, string $channel = 'sms'): void
    {
        $recipient = $customer->cellulare ?? $customer->telefono ?? '';

        if ($this->isMocked) {
            Log::info("[MOCK] OTP {$otp} inviato via {$channel} a {$recipient} (cliente #{$customer->id})");
            return;
        }

        match ($channel) {
            'whatsapp' => $this->sendWhatsApp($recipient, 'otp_signature', ['1' => $otp]),
            default    => $this->sendSms($recipient, "Il tuo codice di firma IspManager è: {$otp}. Valido 24h."),
        };
    }

    /**
     * Invia notifica WhatsApp tramite template pre-approvato Meta WABA.
     *
     * @param array<string, string> $params Variabili template {{1}}, {{2}}, ...
     */
    public function sendWhatsApp(string $phone, string $templateName, array $params = []): void
    {
        if ($this->isMocked) {
            Log::info("[MOCK] WhatsApp template={$templateName} to={$phone}", $params);
            return;
        }

        // TODO Fase 5: implementare via Meta WABA API
        // La logica completa è nel Modulo AI (WhatsAppService)
        Log::warning("WhatsApp non ancora implementato. Template={$templateName} to={$phone}");
    }

    /**
     * Invia SMS tramite Twilio.
     */
    public function sendSms(string $phone, string $message): void
    {
        if ($this->isMocked) {
            Log::info("[MOCK] SMS to={$phone}: {$message}");
            return;
        }

        // TODO Fase 5: implementare via Twilio SDK
        Log::warning("SMS non ancora implementato. to={$phone}");
    }
}
