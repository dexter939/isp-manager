<?php

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Models\AiConversation;
use Modules\Maintenance\Services\TicketService;

/**
 * Integrazione Twilio Voice per chiamate inbound/outbound.
 *
 * Flusso inbound:
 *   1. Twilio chiama il webhook POST /ai/voice/inbound
 *   2. Rispondiamo con TwiML (<Say>, <Gather>)
 *   3. I dati del chiamante vengono usati per identificare il cliente
 *   4. Il trascritto della chiamata può generare un ticket
 *
 * Flusso outbound:
 *   1. initiateCall() → Twilio API REST
 *   2. Twilio carica il TwiML dall'URL statusCallback
 */
class VoiceService
{
    private bool $isMocked;
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;

    public function __construct(
        private readonly TicketWriterService $ticketWriter,
    ) {
        $this->isMocked    = (bool) config('app.carrier_mock', false);
        $this->accountSid  = config('services.twilio.account_sid', '');
        $this->authToken   = config('services.twilio.auth_token', '');
        $this->fromNumber  = config('services.twilio.from_number', '');
    }

    /**
     * Genera TwiML per una chiamata inbound (IVR semplice).
     * Accoglie il cliente e raccoglie l'input vocale.
     */
    public function handleInbound(array $params, int $tenantId): string
    {
        $caller     = $params['From'] ?? 'unknown';
        $callSid    = $params['CallSid'] ?? '';
        $transcript = $params['SpeechResult'] ?? null; // se <Gather input="speech">

        Log::info("Twilio inbound da {$caller} (SID: {$callSid})");

        if ($transcript) {
            // Testo già raccolto → genera ticket
            $this->ticketWriter->draftFromText(
                tenantId:     $tenantId,
                customerText: $transcript,
            );

            return $this->twiml(
                '<Say language="it-IT">Grazie. Abbiamo registrato la sua segnalazione. ' .
                'Riceverà una conferma via SMS. Arrivederci.</Say><Hangup/>'
            );
        }

        // Prima chiamata → richiedi input vocale
        return $this->twiml(
            '<Say language="it-IT">Benvenuto nel supporto tecnico. ' .
            'Dopo il segnale, descriva brevemente il suo problema.</Say>' .
            '<Gather input="speech" timeout="10" language="it-IT" ' .
            'action="/api/v1/ai/voice/inbound">' .
            '<Say language="it-IT">Parli ora.</Say>' .
            '</Gather>'
        );
    }

    /**
     * Avvia una chiamata outbound (es. richiamata proattiva al cliente).
     */
    public function initiateCall(
        string $toNumber,
        string $twimlUrl,
        ?string $statusCallbackUrl = null,
    ): string {
        if ($this->isMocked) {
            Log::info("[MOCK] Twilio outbound → {$toNumber}");
            return 'MOCK_CALL_SID_' . uniqid();
        }

        $response = Http::withBasicAuth($this->accountSid, $this->authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Calls.json", array_filter([
                'To'             => $toNumber,
                'From'           => $this->fromNumber,
                'Url'            => $twimlUrl,
                'StatusCallback' => $statusCallbackUrl,
            ]));

        $response->throw();

        return $response->json('sid');
    }

    /**
     * Verifica la firma Twilio (X-Twilio-Signature) per proteggere il webhook.
     */
    public function verifyWebhookSignature(string $url, array $params, string $signature): bool
    {
        // Algoritmo Twilio: HMAC-SHA1 su URL + parametri sorted alfabeticamente
        ksort($params);
        $data     = $url . implode('', array_map(fn($k, $v) => $k . $v, array_keys($params), $params));
        $expected = base64_encode(hash_hmac('sha1', $data, $this->authToken, binary: true));
        return hash_equals($expected, $signature);
    }

    /**
     * Processa un SMS inbound (Twilio SMS → stesso handler dei ticket).
     */
    public function processInboundSms(array $params, int $tenantId): string
    {
        $from = $params['From'] ?? '';
        $body = $params['Body'] ?? '';

        if (!$body) {
            return $this->twiml('<Message>Messaggio non ricevuto. Riprova.</Message>');
        }

        ['ticket' => $ticket] = $this->ticketWriter->draftFromText(
            tenantId:     $tenantId,
            customerText: $body,
        );

        return $this->twiml(
            "<Message>Segnalazione ricevuta. Ticket #{$ticket->ticket_number} aperto. " .
            "La ricontatteremo al più presto.</Message>"
        );
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function twiml(string $body): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Response>{$body}</Response>";
    }
}
