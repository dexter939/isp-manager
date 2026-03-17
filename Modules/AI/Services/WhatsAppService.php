<?php

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\WhatsAppMessage;
use Modules\Contracts\Models\Customer;

/**
 * Integrazione Meta WhatsApp Business API (WABA).
 *
 * Flusso inbound: webhook → processInbound() → TicketWriterService
 * Flusso outbound: send() / sendTemplate()
 *
 * Autenticazione: Bearer token (System User Token) da WABA.
 * Phone number ID: identifica il numero mittente del business.
 */
class WhatsAppService
{
    private const API_VERSION = 'v19.0';

    private bool $isMocked;
    private string $apiToken;
    private string $phoneNumberId;
    private string $baseUrl;

    public function __construct(
        private readonly TicketWriterService $ticketWriter,
    ) {
        $this->isMocked      = (bool) config('app.carrier_mock', false);
        $this->apiToken      = config('services.whatsapp.token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->baseUrl       = 'https://graph.facebook.com/' . self::API_VERSION;
    }

    /**
     * Invia un messaggio di testo.
     */
    public function sendText(
        int $tenantId,
        string $toNumber,
        string $body,
        ?int $customerId = null,
    ): WhatsAppMessage {
        $message = WhatsAppMessage::create([
            'tenant_id'    => $tenantId,
            'customer_id'  => $customerId,
            'direction'    => 'outbound',
            'from_number'  => $this->phoneNumberId,
            'to_number'    => $toNumber,
            'message_type' => 'text',
            'body'         => $body,
            'status'       => 'pending',
        ]);

        if ($this->isMocked) {
            Log::info("[MOCK] WhatsApp: → {$toNumber}: {$body}");
            $message->update(['status' => 'sent', 'sent_at' => now()]);
            return $message;
        }

        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $toNumber,
                'type'              => 'text',
                'text'              => ['body' => $body],
            ]);

        if ($response->successful()) {
            $wabaId = $response->json('messages.0.id');
            $message->update([
                'waba_message_id' => $wabaId,
                'status'          => 'sent',
                'sent_at'         => now(),
            ]);
        } else {
            $error = $response->json('error.message', 'Unknown error');
            $message->markFailed($error);
            Log::error("WhatsApp send failed → {$toNumber}: {$error}");
        }

        return $message;
    }

    /**
     * Invia un messaggio template approvato da Meta.
     */
    public function sendTemplate(
        int $tenantId,
        string $toNumber,
        string $templateName,
        string $languageCode = 'it',
        array $components = [],
        ?int $customerId = null,
    ): WhatsAppMessage {
        $message = WhatsAppMessage::create([
            'tenant_id'       => $tenantId,
            'customer_id'     => $customerId,
            'direction'       => 'outbound',
            'from_number'     => $this->phoneNumberId,
            'to_number'       => $toNumber,
            'message_type'    => 'template',
            'template_name'   => $templateName,
            'template_params' => $components,
            'status'          => 'pending',
        ]);

        if ($this->isMocked) {
            Log::info("[MOCK] WhatsApp template '{$templateName}' → {$toNumber}");
            $message->update(['status' => 'sent', 'sent_at' => now()]);
            return $message;
        }

        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $toNumber,
                'type'              => 'template',
                'template'          => [
                    'name'       => $templateName,
                    'language'   => ['code' => $languageCode],
                    'components' => $components,
                ],
            ]);

        if ($response->successful()) {
            $message->update([
                'waba_message_id' => $response->json('messages.0.id'),
                'status'          => 'sent',
                'sent_at'         => now(),
            ]);
        } else {
            $message->markFailed($response->json('error.message', 'Unknown error'));
        }

        return $message;
    }

    /**
     * Processa un messaggio inbound dal webhook Meta.
     * Genera automaticamente un ticket via AI se il messaggio è di supporto.
     */
    public function processInbound(array $payload, int $tenantId): void
    {
        $entry   = $payload['entry'][0] ?? null;
        $changes = $entry['changes'][0]['value'] ?? null;

        if (!$changes) {
            return;
        }

        // Aggiornamenti status (delivered/read)
        foreach ($changes['statuses'] ?? [] as $status) {
            $this->handleStatusUpdate($status);
        }

        // Messaggi inbound
        foreach ($changes['messages'] ?? [] as $msg) {
            $this->handleInboundMessage($msg, $tenantId);
        }
    }

    /**
     * Verifica la firma del webhook Meta (X-Hub-Signature-256).
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $appSecret = config('services.whatsapp.app_secret', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Invio massivo a lista di numeri — usato per alert BTS down, manutenzioni programmate.
     * Meta WABA richiede template pre-approvati per invio outbound massivo.
     * Rate limit: 250 msg/secondo; usa chunk per rispettare i limiti.
     *
     * @param string[] $toNumbers Numeri in formato E.164 (+39...)
     * @param string $templateName Template approvato da Meta
     * @param array $components Parametri variabili del template
     */
    public function sendBulk(
        int $tenantId,
        array $toNumbers,
        string $templateName,
        array $components = [],
        string $languageCode = 'it',
    ): int {
        $sent = 0;

        foreach (array_chunk($toNumbers, 250) as $chunk) {
            foreach ($chunk as $toNumber) {
                try {
                    $msg = $this->sendTemplate(
                        tenantId:     $tenantId,
                        toNumber:     $toNumber,
                        templateName: $templateName,
                        languageCode: $languageCode,
                        components:   $components,
                    );

                    if ($msg->status !== 'failed') {
                        $sent++;
                    }
                } catch (\Throwable $e) {
                    Log::warning("WhatsApp sendBulk: fallito per {$toNumber} — {$e->getMessage()}");
                }
            }

            // Pausa tra chunk per rispettare rate limit Meta
            if (!$this->isMocked) {
                usleep(1_000_000); // 1 secondo
            }
        }

        Log::info("WhatsApp sendBulk '{$templateName}': {$sent}/" . count($toNumbers) . " inviati");

        return $sent;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function handleInboundMessage(array $msg, int $tenantId): void
    {
        $from    = $msg['from'];
        $type    = $msg['type'] ?? 'text';
        $wabaId  = $msg['id'];
        $body    = $msg['text']['body'] ?? '';

        // Evita duplicati (Meta può reinviare lo stesso messaggio)
        if (WhatsAppMessage::where('waba_message_id', $wabaId)->exists()) {
            return;
        }

        $inbound = WhatsAppMessage::create([
            'tenant_id'      => $tenantId,
            'direction'      => 'inbound',
            'from_number'    => $from,
            'to_number'      => $this->phoneNumberId,
            'waba_message_id' => $wabaId,
            'message_type'   => $type,
            'body'           => $body,
            'status'         => 'delivered',
            'delivered_at'   => now(),
        ]);

        if (!$body) {
            return; // ignora media senza testo
        }

        // Genera ticket via AI e risponde
        ['ticket' => $ticket] = $this->ticketWriter->draftFromText(
            tenantId:     $tenantId,
            customerText: $body,
        );

        $this->sendTemplate(
            tenantId:     $tenantId,
            toNumber:     $from,
            templateName: 'ticket_created_it',
            components:   [
                [
                    'type'       => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $ticket->ticket_number],
                    ],
                ],
            ],
        );
    }

    private function handleStatusUpdate(array $status): void
    {
        $message = WhatsAppMessage::where('waba_message_id', $status['id'])->first();
        if (!$message) {
            return;
        }

        match($status['status']) {
            'delivered' => $message->markDelivered(),
            'read'      => $message->markRead(),
            'failed'    => $message->markFailed($status['errors'][0]['message'] ?? 'Unknown'),
            default     => null,
        };
    }
}
