<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EmailTemplateService;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;

class TicketWebController extends Controller
{
    // ── Lista ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('trouble_tickets as t')
            ->leftJoin('customers as c', 't.customer_id', '=', 'c.id')
            ->leftJoin('users as u', 't.assigned_to', '=', 'u.id')
            ->where('t.tenant_id', $tenantId)
            ->whereNull('t.deleted_at')
            ->selectRaw("t.*,
                COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS customer_full_name,
                u.name AS assigned_name");

        if ($search = $request->input('search')) {
            $s = '%' . strtolower($search) . '%';
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(t.ticket_number) LIKE ?', [$s])
                  ->orWhereRaw('LOWER(t.title) LIKE ?', [$s])
                  ->orWhereRaw("LOWER(COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome)) LIKE ?", [$s]);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('t.status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('t.priority', $priority);
        }

        if ($request->boolean('overdue')) {
            $query->whereNotIn('t.status', ['resolved', 'closed', 'cancelled'])
                  ->whereNotNull('t.due_at')
                  ->whereRaw('t.due_at < NOW()');
        }

        $tickets = $query->orderByRaw("CASE t.priority
            WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderByDesc('t.opened_at')
            ->paginate(25)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    // ── Dettaglio ─────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $ticket = DB::table('trouble_tickets as t')
            ->leftJoin('customers as c', 't.customer_id', '=', 'c.id')
            ->leftJoin('contracts as ct', 't.contract_id', '=', 'ct.id')
            ->leftJoin('service_plans as sp', 'ct.service_plan_id', '=', 'sp.id')
            ->leftJoin('users as u', 't.assigned_to', '=', 'u.id')
            ->where('t.id', $id)
            ->where('t.tenant_id', $tenantId)
            ->selectRaw("t.*,
                COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS customer_full_name,
                c.email AS customer_email,
                ct.contract_number,
                sp.name AS plan_name,
                sp.sla_type,
                sp.mtr_hours,
                u.name AS assigned_name")
            ->firstOrFail();

        $notes = DB::table('ticket_notes as n')
            ->leftJoin('users as u', 'n.user_id', '=', 'u.id')
            ->where('n.ticket_id', $id)
            ->orderBy('n.created_at')
            ->selectRaw('n.*, u.name AS author_name')
            ->get();

        $users = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Allowed status transitions from current status
        $currentStatus = TicketStatus::from($ticket->status);
        $transitions   = $currentStatus->allowedTransitions();

        // SLA info
        $priority          = TicketPriority::from($ticket->priority);
        $slaResolutionH    = $ticket->mtr_hours ?? $priority->resolutionHours();
        $slaFirstResponseH = $priority->firstResponseHours();
        $due               = $ticket->due_at ? \Carbon\Carbon::parse($ticket->due_at) : null;
        $openedAt          = \Carbon\Carbon::parse($ticket->opened_at);
        $firstResponseDue  = $openedAt->copy()->addHours($slaFirstResponseH);

        return view('tickets.show', compact(
            'ticket', 'notes', 'users', 'transitions',
            'priority', 'slaResolutionH', 'slaFirstResponseH',
            'due', 'openedAt', 'firstResponseDue'
        ));
    }

    // ── Crea ──────────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $tenantId   = auth()->user()->tenant_id;
        $contractId = $request->input('contract_id');

        $customers = DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderByRaw("COALESCE(ragione_sociale, nome || ' ' || cognome)")
            ->get(['id', 'ragione_sociale', 'nome', 'cognome']);

        return view('tickets.create', compact('contractId', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'priority'    => 'required|in:low,medium,high,critical',
            'type'        => 'nullable|in:assurance,billing,provisioning,other',
            'customer_id' => 'nullable|integer',
            'contract_id' => 'nullable|integer',
        ]);

        $service = app(\Modules\Maintenance\Services\TicketService::class);
        $ticket  = $service->create(array_merge($validated, [
            'tenant_id'  => auth()->user()->tenant_id,
            'created_by' => auth()->id(),
            'source'     => 'web',
        ]));

        // Email di conferma al cliente
        if ($ticket->customer_id) {
            $customer = DB::table('customers')->where('id', $ticket->customer_id)->first();
            if ($customer?->email) {
                $name = $customer->ragione_sociale ?: trim($customer->nome . ' ' . $customer->cognome);
                app(EmailTemplateService::class)->send(
                    slug:      'ticket_opened',
                    tenantId:  auth()->user()->tenant_id,
                    toEmail:   $customer->email,
                    toName:    $name,
                    variables: [
                        'customer_name'     => $name,
                        'ticket_number'     => $ticket->ticket_number,
                        'ticket_title'      => $ticket->title,
                        'ticket_priority'   => ucfirst($ticket->priority),
                        'ticket_opened_at'  => now()->format('d/m/Y H:i'),
                    ]
                );
            }
        }

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket ' . $ticket->ticket_number . ' aperto.');
    }

    // ── Cambio stato ──────────────────────────────────────────────────────────

    public function transition(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $ticket   = DB::table('trouble_tickets')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $newStatus = $request->input('status');

        $current  = TicketStatus::from($ticket->status);
        $target   = TicketStatus::tryFrom($newStatus);

        if (!$target || !$current->canTransitionTo($target)) {
            return back()->with('error', "Transizione da {$current->label()} a " . ($target?->label() ?? $newStatus) . " non consentita.");
        }

        $update = ['status' => $target->value, 'updated_at' => now()];

        if ($target === TicketStatus::InProgress && !$ticket->first_response_at) {
            $update['first_response_at'] = now();
        }
        if (in_array($target, [TicketStatus::Resolved, TicketStatus::Closed])) {
            $update['resolved_at'] = $ticket->resolved_at ?? now();
        }
        if ($target === TicketStatus::Closed) {
            $update['closed_at'] = now();
        }

        DB::table('trouble_tickets')->where('id', $id)->update($update);

        if ($notes = $request->input('resolution_notes')) {
            DB::table('trouble_tickets')->where('id', $id)->update(['resolution_notes' => $notes]);
        }

        // System note
        DB::table('ticket_notes')->insert([
            'ticket_id'   => $id,
            'user_id'     => auth()->id(),
            'body'        => "Stato cambiato: {$current->label()} → {$target->label()}" . ($notes ? " — {$notes}" : ''),
            'type'        => 'status_change',
            'is_internal' => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Email di risoluzione al cliente
        if (in_array($target, [TicketStatus::Resolved]) && $ticket->customer_id) {
            $customer = DB::table('customers')->where('id', $ticket->customer_id)->first();
            if ($customer?->email) {
                $name = $customer->ragione_sociale ?: trim($customer->nome . ' ' . $customer->cognome);
                app(EmailTemplateService::class)->send(
                    slug:      'ticket_resolved',
                    tenantId:  auth()->user()->tenant_id,
                    toEmail:   $customer->email,
                    toName:    $name,
                    variables: [
                        'customer_name'              => $name,
                        'ticket_number'              => $ticket->ticket_number,
                        'ticket_title'               => $ticket->title,
                        'ticket_resolved_at'         => now()->format('d/m/Y H:i'),
                        'ticket_resolution_notes'    => $notes ?? '',
                    ]
                );
            }
        }

        return back()->with('success', "Stato aggiornato: {$target->label()}.");
    }

    // ── Assegnazione ─────────────────────────────────────────────────────────

    public function assign(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $ticket   = DB::table('trouble_tickets')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $userId   = $request->input('assigned_to') ?: null;
        $previous = $ticket->assigned_to;

        DB::table('trouble_tickets')->where('id', $id)->update([
            'assigned_to' => $userId,
            'updated_at'  => now(),
        ]);

        // Transition to in_progress on first assignment if still open
        if ($userId && $ticket->status === 'open') {
            DB::table('trouble_tickets')->where('id', $id)->update([
                'status'             => 'in_progress',
                'first_response_at'  => $ticket->first_response_at ?? now(),
            ]);
        }

        $assignedUser = $userId
            ? DB::table('users')->where('id', $userId)->value('name')
            : 'Nessuno';

        DB::table('ticket_notes')->insert([
            'ticket_id'   => $id,
            'user_id'     => auth()->id(),
            'body'        => "Ticket assegnato a: {$assignedUser}",
            'type'        => 'assignment',
            'is_internal' => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return back()->with('success', "Ticket assegnato a {$assignedUser}.");
    }

    // ── Nota ─────────────────────────────────────────────────────────────────

    public function addNote(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        DB::table('trouble_tickets')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $request->validate(['body' => 'required|string|max:5000']);

        DB::table('ticket_notes')->insert([
            'ticket_id'   => $id,
            'user_id'     => auth()->id(),
            'body'        => $request->input('body'),
            'type'        => 'comment',
            'is_internal' => $request->boolean('is_internal'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // First response tracking
        $ticket = DB::table('trouble_tickets')->where('id', $id)->first();
        if (!$ticket->first_response_at && !$request->boolean('is_internal')) {
            DB::table('trouble_tickets')->where('id', $id)->update([
                'first_response_at' => now(),
                'updated_at'        => now(),
            ]);
        }

        return back()->with('success', 'Nota aggiunta.');
    }

    // ── SLA Dashboard ─────────────────────────────────────────────────────────

    public function sla(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // KPI
        $stats = [
            'open'     => DB::table('trouble_tickets')->where('tenant_id', $tenantId)
                            ->whereIn('status', ['open', 'in_progress', 'pending'])->count(),
            'breached' => DB::table('trouble_tickets')->where('tenant_id', $tenantId)
                            ->whereIn('status', ['open', 'in_progress', 'pending'])
                            ->whereNotNull('due_at')->whereRaw('due_at < NOW()')->count(),
            'at_risk'  => DB::table('trouble_tickets')->where('tenant_id', $tenantId)
                            ->whereIn('status', ['open', 'in_progress', 'pending'])
                            ->whereNotNull('due_at')
                            ->whereRaw('due_at BETWEEN NOW() AND NOW() + INTERVAL \'4 hours\'')->count(),
            'no_first_response' => DB::table('trouble_tickets')->where('tenant_id', $tenantId)
                            ->whereIn('status', ['open', 'in_progress', 'pending'])
                            ->whereNull('first_response_at')
                            ->whereRaw("opened_at + (CASE priority
                                WHEN 'critical' THEN INTERVAL '2 hours'
                                WHEN 'high'     THEN INTERVAL '8 hours'
                                WHEN 'medium'   THEN INTERVAL '24 hours'
                                ELSE INTERVAL '48 hours' END) < NOW()")
                            ->count(),
        ];

        // Breached tickets
        $breached = DB::table('trouble_tickets as t')
            ->leftJoin('customers as c', 't.customer_id', '=', 'c.id')
            ->leftJoin('users as u', 't.assigned_to', '=', 'u.id')
            ->where('t.tenant_id', $tenantId)
            ->whereIn('t.status', ['open', 'in_progress', 'pending'])
            ->whereNotNull('t.due_at')
            ->whereRaw('t.due_at < NOW()')
            ->selectRaw("t.id, t.ticket_number, t.title, t.priority, t.status, t.due_at, t.opened_at,
                COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS customer_full_name,
                u.name AS assigned_name,
                EXTRACT(EPOCH FROM (NOW() - t.due_at))/3600 AS hours_overdue")
            ->orderByRaw("CASE t.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('t.due_at')
            ->get();

        // At-risk tickets
        $atRisk = DB::table('trouble_tickets as t')
            ->leftJoin('customers as c', 't.customer_id', '=', 'c.id')
            ->leftJoin('users as u', 't.assigned_to', '=', 'u.id')
            ->where('t.tenant_id', $tenantId)
            ->whereIn('t.status', ['open', 'in_progress', 'pending'])
            ->whereNotNull('t.due_at')
            ->whereRaw("t.due_at BETWEEN NOW() AND NOW() + INTERVAL '4 hours'")
            ->selectRaw("t.id, t.ticket_number, t.title, t.priority, t.status, t.due_at, t.opened_at,
                COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS customer_full_name,
                u.name AS assigned_name,
                EXTRACT(EPOCH FROM (t.due_at - NOW()))/3600 AS hours_remaining")
            ->orderBy('t.due_at')
            ->get();

        // SLA compliance last 30 days by priority
        $compliance = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->where('opened_at', '>=', now()->subDays(30))
            ->whereNotNull('resolved_at')
            ->whereNotNull('due_at')
            ->selectRaw("priority,
                COUNT(*) AS total,
                SUM(CASE WHEN resolved_at <= due_at THEN 1 ELSE 0 END) AS within_sla,
                AVG(EXTRACT(EPOCH FROM (resolved_at - opened_at))/3600) AS avg_hours")
            ->groupBy('priority')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->get();

        // First response breach (no first response within SLA)
        $firstResponseBreached = DB::table('trouble_tickets as t')
            ->leftJoin('customers as c', 't.customer_id', '=', 'c.id')
            ->leftJoin('users as u', 't.assigned_to', '=', 'u.id')
            ->where('t.tenant_id', $tenantId)
            ->whereIn('t.status', ['open', 'in_progress', 'pending'])
            ->whereNull('t.first_response_at')
            ->whereRaw("t.opened_at + (CASE t.priority
                WHEN 'critical' THEN INTERVAL '2 hours'
                WHEN 'high'     THEN INTERVAL '8 hours'
                WHEN 'medium'   THEN INTERVAL '24 hours'
                ELSE INTERVAL '48 hours' END) < NOW()")
            ->selectRaw("t.id, t.ticket_number, t.title, t.priority, t.status, t.opened_at,
                COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS customer_full_name,
                u.name AS assigned_name")
            ->orderByRaw("CASE t.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->get();

        return view('tickets.sla', compact('stats', 'breached', 'atRisk', 'compliance', 'firstResponseBreached'));
    }

    // ── Risolvi rapido ────────────────────────────────────────────────────────

    public function resolve(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('trouble_tickets')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['status' => 'resolved', 'resolved_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Ticket contrassegnato come risolto.');
    }
}
