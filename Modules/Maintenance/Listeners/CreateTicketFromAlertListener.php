<?php

declare(strict_types=1);

namespace Modules\Maintenance\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Services\TicketService;
use Modules\Monitoring\Events\NetworkAlertCreated;

/**
 * Al ricevimento di un NetworkAlert critico, apre automaticamente
 * un Trouble Ticket di tipo assurance collegato al contratto/cliente.
 *
 * Non genera ticket per alert MSO (L07) — quelli richiedono gestione manuale.
 */
class CreateTicketFromAlertListener implements ShouldQueue
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function handle(NetworkAlertCreated $event): void
    {
        $alert = $event->alert;

        // Solo alert critici e non MSO
        if (!$alert->isCritical()) {
            return;
        }

        // Alert MSO (L07): NON aprire un ticket singolo, l'operatore gestisce manualmente
        if (($alert->details['error_code'] ?? null) === 'L07') {
            Log::warning("Alert MSO L07 per tenant {$alert->tenant_id}: nessun ticket automatico");
            return;
        }

        $ticket = $this->ticketService->create(
            tenantId:    $alert->tenant_id,
            title:       "Alert critico: {$alert->message}",
            description: $this->buildDescription($alert),
            priority:    TicketPriority::Critical,
            type:        'assurance',
            source:      'ai',
            customerId:  $alert->customer_id,
            contractId:  $alert->contract_id,
        );

        Log::info("Ticket #{$ticket->ticket_number} creato automaticamente da alert #{$alert->id}");
    }

    private function buildDescription(object $alert): string
    {
        $lines = [
            "Alert automatico generato dal sistema di monitoraggio.",
            "Fonte: {$alert->source}",
            "Severità: {$alert->severity}",
            "Tipo: {$alert->type}",
            "Messaggio: {$alert->message}",
        ];

        if ($alert->details) {
            $lines[] = "Dettagli: " . json_encode($alert->details, JSON_UNESCAPED_UNICODE);
        }

        return implode("\n", $lines);
    }
}
