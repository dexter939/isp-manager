<?php

declare(strict_types=1);

namespace Modules\Maintenance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;
use Modules\Maintenance\Models\TicketNote;
use Modules\Maintenance\Models\TroubleTicket;

/**
 * Gestisce il ciclo di vita dei Trouble Ticket (assurance, billing, provisioning).
 *
 * Flusso tipico:
 * 1. create()      → stato Open, calcolo SLA due_at
 * 2. assign()      → assegnazione operatore
 * 3. addNote()     → aggiornamenti / comunicazioni
 * 4. transition()  → cambio stato con validazione
 * 5. resolve()     → stato Resolved + resolution_notes
 * 6. close()       → stato Closed
 */
class TicketService
{
    /**
     * Crea un nuovo ticket.
     */
    public function create(
        int $tenantId,
        string $title,
        string $description,
        TicketPriority $priority = TicketPriority::Medium,
        string $type = 'other',
        string $source = 'manual',
        ?int $customerId = null,
        ?int $contractId = null,
    ): TroubleTicket {
        return DB::transaction(function () use ($tenantId, $title, $description, $priority, $type, $source, $customerId, $contractId) {
            $openedAt = now();
            $dueAt    = $openedAt->copy()->addHours($priority->resolutionHours());

            return TroubleTicket::create([
                'tenant_id'     => $tenantId,
                'customer_id'   => $customerId,
                'contract_id'   => $contractId,
                'ticket_number' => $this->nextTicketNumber($tenantId),
                'title'         => $title,
                'description'   => $description,
                'status'        => TicketStatus::Open->value,
                'priority'      => $priority->value,
                'type'          => $type,
                'source'        => $source,
                'opened_at'     => $openedAt,
                'due_at'        => $dueAt,
            ]);
        });
    }

    /**
     * Assegna il ticket a un operatore.
     */
    public function assign(TroubleTicket $ticket, int $userId, ?string $note = null): TroubleTicket
    {
        return DB::transaction(function () use ($ticket, $userId, $note) {
            $previous = $ticket->assigned_to;

            $ticket->update([
                'assigned_to' => $userId,
                'status'      => $ticket->status === TicketStatus::Open
                    ? TicketStatus::InProgress->value
                    : $ticket->status->value,
            ]);

            $this->addSystemNote($ticket, 'assignment', [
                'from'    => $previous,
                'to'      => $userId,
                'comment' => $note,
            ]);

            return $ticket->fresh();
        });
    }

    /**
     * Cambia lo stato del ticket con validazione macchina a stati.
     */
    public function transition(TroubleTicket $ticket, TicketStatus $target, ?string $note = null): TroubleTicket
    {
        if (!$ticket->status->canTransitionTo($target)) {
            throw new \DomainException(
                "Impossibile passare da [{$ticket->status->value}] a [{$target->value}] per ticket #{$ticket->ticket_number}"
            );
        }

        return DB::transaction(function () use ($ticket, $target, $note) {
            $updates = ['status' => $target->value];

            if ($target === TicketStatus::Resolved) {
                $updates['resolved_at'] = now();
            }
            if ($target === TicketStatus::Closed) {
                $updates['closed_at'] = now();
            }

            $ticket->update($updates);

            $this->addSystemNote($ticket, 'status_change', [
                'from'    => $ticket->getOriginal('status'),
                'to'      => $target->value,
                'comment' => $note,
            ]);

            return $ticket->fresh();
        });
    }

    /**
     * Risolve il ticket con note di risoluzione.
     */
    public function resolve(TroubleTicket $ticket, string $resolutionNotes, ?int $userId = null): TroubleTicket
    {
        $this->transition($ticket, TicketStatus::Resolved);

        $ticket->update(['resolution_notes' => $resolutionNotes]);

        $this->addNote($ticket, $resolutionNotes, userId: $userId, type: 'comment', isInternal: false);

        return $ticket->fresh();
    }

    /**
     * Chiude il ticket.
     */
    public function close(TroubleTicket $ticket, ?string $note = null): TroubleTicket
    {
        return $this->transition($ticket, TicketStatus::Closed, $note);
    }

    /**
     * Aggiunge una nota al ticket.
     */
    public function addNote(
        TroubleTicket $ticket,
        string $body,
        ?int $userId = null,
        string $type = 'comment',
        bool $isInternal = false,
        bool $isAiGenerated = false,
        array $metadata = [],
    ): TicketNote {
        // Registra la prima risposta se non ancora impostata
        if (!$ticket->first_response_at && $userId && !$isInternal) {
            $ticket->update(['first_response_at' => now()]);
        }

        return TicketNote::create([
            'ticket_id'      => $ticket->id,
            'user_id'        => $userId,
            'body'           => $body,
            'type'           => $type,
            'is_internal'    => $isInternal,
            'is_ai_generated' => $isAiGenerated,
            'metadata'       => $metadata ?: null,
        ]);
    }

    /**
     * Imposta il carrier ticket ID (ID ticket lato Open Fiber / FiberCop).
     */
    public function setCarrierTicket(TroubleTicket $ticket, string $carrier, string $carrierTicketId): void
    {
        $ticket->update([
            'carrier'           => $carrier,
            'carrier_ticket_id' => $carrierTicketId,
        ]);

        Log::info("Ticket #{$ticket->ticket_number}: carrier ticket {$carrier} → {$carrierTicketId}");
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function addSystemNote(TroubleTicket $ticket, string $type, array $metadata): void
    {
        TicketNote::create([
            'ticket_id' => $ticket->id,
            'user_id'   => null,
            'body'      => '',
            'type'      => $type,
            'is_internal' => true,
            'metadata'  => $metadata,
        ]);
    }

    /**
     * Genera il prossimo numero ticket: TK-YYYY-NNNNNN
     */
    private function nextTicketNumber(int $tenantId): string
    {
        $year = now()->year;

        $last = TroubleTicket::where('tenant_id', $tenantId)
            ->whereYear('opened_at', $year)
            ->lockForUpdate()
            ->max(DB::raw("CAST(SPLIT_PART(ticket_number, '-', 3) AS INTEGER)"));

        return sprintf('TK-%d-%06d', $year, ($last ?? 0) + 1);
    }
}
