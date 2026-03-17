<?php

declare(strict_types=1);

namespace Modules\AI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use Modules\AI\Http\Requests\DraftTicketRequest;
use Modules\AI\Http\Requests\ChatRequest;
use Modules\AI\Models\AiConversation;
use Modules\AI\Services\TicketWriterService;

class AIController extends ApiController
{
    public function __construct(
        private readonly TicketWriterService $ticketWriter,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Genera un ticket strutturato da testo libero.
     */
    public function draftTicket(DraftTicketRequest $request): JsonResponse
    {
        $data = $request->validated();

        ['ticket' => $ticket, 'conversation' => $conversation] = $this->ticketWriter->draftFromText(
            tenantId:     auth()->user()->tenant_id,
            customerText: $data['text'],
            customerId:   $data['customer_id'] ?? null,
            contractId:   $data['contract_id'] ?? null,
        );

        return response()->json([
            'ticket'       => $ticket,
            'conversation' => $conversation->only(['id', 'total_input_tokens', 'total_output_tokens']),
        ], 201);
    }

    /**
     * Invia un messaggio in una conversazione AI esistente.
     */
    public function chat(ChatRequest $request, AiConversation $conversation): JsonResponse
    {
        $data = $request->validated();

        if (!$conversation->isActive()) {
            return response()->json(['error' => 'Conversazione non più attiva'], 422);
        }

        $reply = $this->ticketWriter->chat($conversation, $data['message']);

        return response()->json(['reply' => $reply]);
    }
}
