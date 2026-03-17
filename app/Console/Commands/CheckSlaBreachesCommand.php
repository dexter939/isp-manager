<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSlaBreachesCommand extends Command
{
    protected $signature   = 'tickets:check-sla {--dry-run : Mostra le violazioni senza inserire note}';
    protected $description = 'Controlla le violazioni SLA, crea note di sistema e scala i ticket critici';

    public function handle(): int
    {
        $now = now();

        // ── 1. Nuove violazioni SLA risoluzione ───────────────────────────────
        $newBreaches = DB::table('trouble_tickets')
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->whereNull('deleted_at')
            ->whereNotNull('due_at')
            ->whereRaw('due_at <= NOW()')
            ->whereNotExists(function ($q) {
                $q->from('ticket_notes')
                  ->whereColumn('ticket_id', 'trouble_tickets.id')
                  ->where('type', 'sla_breach');
            })
            ->get(['id', 'tenant_id', 'ticket_number', 'priority', 'due_at', 'assigned_to']);

        $this->info("Nuove violazioni SLA: {$newBreaches->count()}");

        if (!$this->option('dry-run')) {
            foreach ($newBreaches as $ticket) {
                DB::table('ticket_notes')->insert([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => null,
                    'body'        => sprintf(
                        'SLA risoluzione scaduto: termine era %s (priorità %s).',
                        \Carbon\Carbon::parse($ticket->due_at)->format('d/m/Y H:i'),
                        strtoupper($ticket->priority)
                    ),
                    'type'        => 'sla_breach',
                    'is_internal' => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $this->line("  ✗ Breach: {$ticket->ticket_number}");
            }
        }

        // ── 2. Escalation: critical/high scaduti da più di 2h senza nota escalation ──
        $toEscalate = DB::table('trouble_tickets')
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->whereNull('deleted_at')
            ->whereIn('priority', ['critical', 'high'])
            ->whereNotNull('due_at')
            ->whereRaw("due_at <= NOW() - INTERVAL '2 hours'")
            ->whereNotExists(function ($q) {
                $q->from('ticket_notes')
                  ->whereColumn('ticket_id', 'trouble_tickets.id')
                  ->where('type', 'sla_escalation');
            })
            ->get(['id', 'ticket_number', 'priority', 'due_at', 'assigned_to']);

        $this->info("Ticket da escalare: {$toEscalate->count()}");

        if (!$this->option('dry-run')) {
            foreach ($toEscalate as $ticket) {
                DB::table('ticket_notes')->insert([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => null,
                    'body'        => sprintf(
                        'ESCALATION AUTOMATICA: ticket %s scaduto da oltre 2 ore. Richiedeintervento immediato.',
                        strtoupper($ticket->priority)
                    ),
                    'type'        => 'sla_escalation',
                    'is_internal' => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $this->line("  ↑ Escalation: {$ticket->ticket_number}");
            }
        }

        // ── 3. Prima risposta scaduta ──────────────────────────────────────────
        $noFirstResponse = DB::table('trouble_tickets')
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->whereNull('deleted_at')
            ->whereNull('first_response_at')
            ->whereRaw("opened_at + (CASE priority
                WHEN 'critical' THEN INTERVAL '2 hours'
                WHEN 'high'     THEN INTERVAL '8 hours'
                WHEN 'medium'   THEN INTERVAL '24 hours'
                ELSE INTERVAL '48 hours' END) <= NOW()")
            ->whereNotExists(function ($q) {
                $q->from('ticket_notes')
                  ->whereColumn('ticket_id', 'trouble_tickets.id')
                  ->where('type', 'sla_first_response_breach');
            })
            ->get(['id', 'ticket_number', 'priority', 'opened_at']);

        $this->info("Prima risposta scaduta: {$noFirstResponse->count()}");

        if (!$this->option('dry-run')) {
            foreach ($noFirstResponse as $ticket) {
                DB::table('ticket_notes')->insert([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => null,
                    'body'        => "SLA prima risposta scaduto: nessuna risposta ricevuta entro il termine contrattuale.",
                    'type'        => 'sla_first_response_breach',
                    'is_internal' => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $this->line("  ! Prima risposta: {$ticket->ticket_number}");
            }
        }

        $this->newLine();
        $this->info('SLA check completato.');

        return self::SUCCESS;
    }
}
