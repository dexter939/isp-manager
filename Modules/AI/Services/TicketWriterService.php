<?php

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Log;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\Contracts\Models\Contract;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Models\TroubleTicket;
use Modules\Maintenance\Services\TicketService;
use Modules\Monitoring\Models\LineTestResult;
use Modules\Network\Models\RadiusSession;

/**
 * Genera automaticamente Trouble Ticket strutturati da descrizioni in linguaggio naturale
 * usando Claude API (Anthropic SDK).
 *
 * Input: testo libero del cliente (da WhatsApp, portale self-care, call center)
 * Output: TroubleTicket con titolo, descrizione, tipo e priorità proposta
 */
class TicketWriterService
{
    private const MODEL = 'claude-sonnet-4-6';

    private bool $isMocked;

    public function __construct(
        private readonly TicketService $ticketService,
    ) {
        $this->isMocked = (bool) config('app.carrier_mock', false);
    }

    /**
     * Analizza il testo del cliente e genera un ticket strutturato.
     *
     * @return array{ticket: \Modules\Maintenance\Models\TroubleTicket, conversation: AiConversation}
     */
    public function draftFromText(
        int $tenantId,
        string $customerText,
        ?int $customerId = null,
        ?int $contractId = null,
    ): array {
        $conversation = AiConversation::create([
            'tenant_id'   => $tenantId,
            'customer_id' => $customerId,
            'contract_id' => $contractId,
            'channel'     => 'internal',
            'purpose'     => 'ticket_draft',
            'status'      => 'active',
            'model'       => self::MODEL,
        ]);

        $systemPrompt = $this->buildSystemPrompt();
        $userMessage  = "Testo cliente:\n\n{$customerText}";

        if ($this->isMocked) {
            return $this->mockDraft($conversation, $customerText, $customerId, $contractId, $tenantId);
        }

        $response = $this->callClaude($conversation, $systemPrompt, $userMessage);
        $parsed   = $this->parseClaudeResponse($response);

        $ticket = $this->ticketService->create(
            tenantId:    $tenantId,
            title:       $parsed['title'],
            description: $parsed['description'],
            priority:    TicketPriority::from($parsed['priority']),
            type:        $parsed['type'],
            source:      'ai',
            customerId:  $customerId,
            contractId:  $contractId,
        );

        $this->ticketService->addNote(
            ticket:        $ticket,
            body:          "Testo originale cliente:\n\n{$customerText}",
            type:          'system',
            isInternal:    true,
            isAiGenerated: true,
        );

        $conversation->update(['ticket_id' => $ticket->id, 'status' => 'completed']);

        return compact('ticket', 'conversation');
    }

    /**
     * Risponde in linguaggio naturale a un messaggio di supporto.
     * Mantiene il contesto della conversazione su più turni.
     */
    public function chat(AiConversation $conversation, string $userMessage): string
    {
        if ($this->isMocked) {
            Log::info("[MOCK] AI chat per conversation #{$conversation->id}");
            return "Grazie per averci contattato. Un nostro operatore prenderà in carico la sua richiesta a breve.";
        }

        $history = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get()
            ->map(fn(AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        $history[] = ['role' => 'user', 'content' => $userMessage];

        return $this->callClaude($conversation, $this->buildSupportSystemPrompt(), null, $history);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function callClaude(
        AiConversation $conversation,
        string $system,
        ?string $userMessage,
        array $messages = [],
    ): string {
        $client = \Anthropic::client(config('services.anthropic.api_key'));

        if ($userMessage !== null) {
            $messages = [['role' => 'user', 'content' => $userMessage]];
        }

        $response = $client->messages()->create([
            'model'      => self::MODEL,
            'max_tokens' => 1024,
            'system'     => $system,
            'messages'   => $messages,
        ]);

        $content      = $response->content[0]->text ?? '';
        $inputTokens  = $response->usage->inputTokens ?? 0;
        $outputTokens = $response->usage->outputTokens ?? 0;

        // Persiste i messaggi della conversazione
        if ($userMessage !== null) {
            AiMessage::create([
                'conversation_id' => $conversation->id,
                'role'            => 'user',
                'content'         => $userMessage,
            ]);
        }

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $content,
            'input_tokens'    => $inputTokens,
            'output_tokens'   => $outputTokens,
            'stop_reason'     => $response->stopReason ?? null,
        ]);

        $conversation->addTokens($inputTokens, $outputTokens);

        return $content;
    }

    private function parseClaudeResponse(string $json): array
    {
        $data = json_decode($json, true);

        if (!$data || !isset($data['title'], $data['description'], $data['type'], $data['priority'])) {
            Log::warning("AI TicketWriter: risposta Claude non parsabile: {$json}");
            return [
                'title'       => 'Segnalazione cliente',
                'description' => $json,
                'type'        => 'other',
                'priority'    => 'medium',
            ];
        }

        return $data;
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Sei un assistente tecnico di un ISP (Internet Service Provider) italiano.
Il tuo compito è analizzare la descrizione di un problema segnalato da un cliente
e generare un Trouble Ticket strutturato in formato JSON.

Rispondi SOLO con JSON valido, senza markdown, con questa struttura:
{
  "title": "titolo breve (max 80 caratteri)",
  "description": "descrizione tecnica dettagliata per il tecnico (max 500 caratteri)",
  "type": "assurance|billing|provisioning|other",
  "priority": "low|medium|high|critical"
}

Linee guida priorità:
- critical: nessuna connettività, servizio completamente interrotto
- high: connettività degradata, perdita pacchetti > 30%
- medium: lentezza, disconnessioni sporadiche
- low: richiesta informazioni, problemi minori

Linee guida tipo:
- assurance: problemi tecnici connettività / ONT / linea
- billing: fatturazione, pagamenti, mandati SDD
- provisioning: attivazione, cambio piano, portabilità
- other: tutto il resto
PROMPT;
    }

    /**
     * Genera un report tecnico formale per l'apertura di un ticket guasto verso il carrier.
     * Raccoglie contesto: parametri CPE (TR-069), ultima sessione RADIUS, line test recente.
     * Output: testo per il campo DESC_TECNICA_GUASTO di OLO_TicketRequest.
     */
    public function generateCarrierReport(TroubleTicket $ticket): string
    {
        $contract = $ticket->contract;

        if ($this->isMocked) {
            Log::info("[MOCK] AI generateCarrierReport per ticket #{$ticket->ticket_number}");
            return "REPORT MOCK — {$ticket->title}\n\nDescrizione: {$ticket->description}";
        }

        // Raccoglie il contesto tecnico disponibile
        $context = $this->buildTechnicalContext($ticket);

        $prompt = <<<PROMPT
Sei un tecnico TLC esperto. Genera un report tecnico formale per apertura ticket guasto
verso operatore wholesale italiano (Open Fiber / FiberCop).

Usa terminologia tecnica appropriata. Il report deve essere in italiano, conciso e professionale.
Evita speculazioni — riporta solo i dati misurati disponibili.

Contesto tecnico raccolto:
{$context}

Sintomi riportati dal cliente: {$ticket->description}

Genera il testo per il campo DESC_TECNICA_GUASTO (max 500 caratteri):
PROMPT;

        $client = \Anthropic::client(config('services.anthropic.api_key'));

        $response = $client->messages()->create([
            'model'      => self::MODEL,
            'max_tokens' => 600,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        return trim($response->content[0]->text ?? $ticket->description);
    }

    private function buildTechnicalContext(TroubleTicket $ticket): string
    {
        $lines = [];
        $contract = $ticket->contract;

        if (!$contract) {
            return 'Nessun contratto associato al ticket.';
        }

        $lines[] = "Contratto #{$contract->id} | Carrier: {$contract->carrier} | UI: {$contract->codice_ui}";

        // Ultima sessione RADIUS
        $session = RadiusSession::where('contract_id', $contract->id)
            ->latest('acct_start')
            ->first();

        if ($session) {
            $duration = $session->durationFormatted();
            $lines[] = "Ultima sessione RADIUS: start={$session->acct_start}, durata={$duration}, IP={$session->framed_ip}";
        }

        // Line test più recente
        $lineTest = LineTestResult::where('contract_id', $contract->id)
            ->where('carrier', $contract->carrier)
            ->latest()
            ->first();

        if ($lineTest) {
            $lines[] = "Line test ({$lineTest->carrier}): result={$lineTest->result}, errore={$lineTest->error_code}";
            if ($lineTest->attenuation) {
                $lines[] = "  Attenuazione: {$lineTest->attenuation} dBm | Distanza fibra: {$lineTest->optical_distance} m";
            }
        }

        return implode("\n", $lines);
    }

    private function buildSupportSystemPrompt(): string
    {
        return <<<'PROMPT'
Sei un assistente di supporto clienti per un ISP italiano.
Rispondi in italiano in modo professionale e conciso.
Non promettere tempistiche specifiche. Non divulgare informazioni interne.
Se il problema è tecnico complesso, informa che un tecnico prenderà in carico la richiesta.
PROMPT;
    }

    private function mockDraft(
        AiConversation $conversation,
        string $customerText,
        ?int $customerId,
        ?int $contractId,
        int $tenantId,
    ): array {
        Log::info("[MOCK] AI TicketWriter: draft da testo cliente");

        $ticket = $this->ticketService->create(
            tenantId:    $tenantId,
            title:       '[MOCK] Segnalazione cliente',
            description: $customerText,
            priority:    TicketPriority::Medium,
            type:        'assurance',
            source:      'ai',
            customerId:  $customerId,
            contractId:  $contractId,
        );

        $conversation->update(['ticket_id' => $ticket->id, 'status' => 'completed']);

        return compact('ticket', 'conversation');
    }
}
