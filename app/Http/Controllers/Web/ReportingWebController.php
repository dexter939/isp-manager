<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingWebController extends Controller
{
    // ── Overview ──────────────────────────────────────────────────────────────

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $now      = now();

        // MRR / ARR
        $mrr = DB::table('contracts')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->sum('monthly_price');
        $arr = $mrr * 12;

        // Contratti
        $activeContracts   = DB::table('contracts')->where('tenant_id', $tenantId)->where('status', 'active')->count();
        $newThisMonth      = DB::table('contracts')->where('tenant_id', $tenantId)->whereMonth('activation_date', $now->month)->whereYear('activation_date', $now->year)->count();
        $churnedThisMonth  = DB::table('contracts')->where('tenant_id', $tenantId)->where('status', 'terminated')->whereMonth('termination_date', $now->month)->whereYear('termination_date', $now->year)->count();

        // Fatture
        $overdueAmount = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->sum('total');
        $paidThisMonth = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('total');

        // Ticket SLA
        $totalResolved = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('resolved_at')
            ->count();
        $slaBreached = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('resolved_at')
            ->whereNotNull('due_at')
            ->whereColumn('resolved_at', '>', 'due_at')
            ->count();
        $slaRate = $totalResolved > 0 ? round((1 - $slaBreached / $totalResolved) * 100, 1) : 100;

        // Revenue ultimi 12 mesi per grafico
        $revenueChart = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['paid', 'issued', 'overdue'])
            ->where('issue_date', '>=', $now->copy()->subMonths(11)->startOfMonth())
            ->selectRaw("TO_CHAR(issue_date, 'YYYY-MM') AS month, SUM(total) AS revenue, SUM(CASE WHEN status='paid' THEN total ELSE 0 END) AS collected")
            ->groupByRaw("TO_CHAR(issue_date, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(issue_date, 'YYYY-MM')")
            ->get();

        // Contratti per carrier
        $byCarrier = DB::table('contracts')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->selectRaw('carrier, COUNT(*) AS cnt')
            ->groupBy('carrier')
            ->pluck('cnt', 'carrier');

        return view('reporting.index', compact(
            'mrr', 'arr', 'activeContracts', 'newThisMonth', 'churnedThisMonth',
            'overdueAmount', 'paidThisMonth', 'slaRate', 'revenueChart', 'byCarrier'
        ));
    }

    // ── Revenue ───────────────────────────────────────────────────────────────

    public function revenue(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year     = (int) $request->input('year', now()->year);

        // Per mese nell'anno selezionato
        $monthly = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->whereYear('issue_date', $year)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw("EXTRACT(MONTH FROM issue_date)::int AS month,
                SUM(total)                                      AS invoiced,
                SUM(CASE WHEN status='paid' THEN total ELSE 0 END) AS collected,
                COUNT(*)                                        AS invoice_count")
            ->groupByRaw('EXTRACT(MONTH FROM issue_date)::int')
            ->orderByRaw('EXTRACT(MONTH FROM issue_date)::int')
            ->get()
            ->keyBy('month');

        // Top 10 clienti per fatturato anno
        $topCustomers = DB::table('invoices as i')
            ->join('customers as c', 'i.customer_id', '=', 'c.id')
            ->where('i.tenant_id', $tenantId)
            ->whereYear('i.issue_date', $year)
            ->whereNotIn('i.status', ['cancelled', 'draft'])
            ->selectRaw("c.id,
                COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS full_name,
                SUM(i.total) AS total_invoiced,
                SUM(CASE WHEN i.status='paid' THEN i.total ELSE 0 END)  AS total_paid,
                COUNT(*) AS invoice_count")
            ->groupBy('c.id', 'c.ragione_sociale', 'c.nome', 'c.cognome')
            ->orderByDesc('total_invoiced')
            ->limit(10)
            ->get();

        // Per metodo di pagamento
        $byMethod = DB::table('payments as p')
            ->join('invoices as i', 'p.invoice_id', '=', 'i.id')
            ->where('p.tenant_id', $tenantId)
            ->whereYear('p.processed_at', $year)
            ->where('p.status', 'completed')
            ->selectRaw('p.method, SUM(p.amount) AS total, COUNT(*) AS cnt')
            ->groupBy('p.method')
            ->get();

        $years = range(now()->year, max(now()->year - 4, 2024));

        return view('reporting.revenue', compact('monthly', 'topCustomers', 'byMethod', 'year', 'years'));
    }

    // ── Contratti ─────────────────────────────────────────────────────────────

    public function contracts(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year     = (int) $request->input('year', now()->year);

        // Attivi/Nuovi/Chiusi per mese
        $monthly = DB::table('contracts')
            ->where('tenant_id', $tenantId)
            ->whereYear('activation_date', $year)
            ->selectRaw("EXTRACT(MONTH FROM activation_date)::int AS month, COUNT(*) AS new_contracts")
            ->groupByRaw('EXTRACT(MONTH FROM activation_date)::int')
            ->orderByRaw('EXTRACT(MONTH FROM activation_date)::int')
            ->get()->keyBy('month');

        $churnByMonth = DB::table('contracts')
            ->where('tenant_id', $tenantId)
            ->where('status', 'terminated')
            ->whereYear('termination_date', $year)
            ->selectRaw("EXTRACT(MONTH FROM termination_date)::int AS month, COUNT(*) AS churned")
            ->groupByRaw('EXTRACT(MONTH FROM termination_date)::int')
            ->orderByRaw('EXTRACT(MONTH FROM termination_date)::int')
            ->get()->keyBy('month');

        // Per piano di servizio
        $byPlan = DB::table('contracts as c')
            ->join('service_plans as sp', 'c.service_plan_id', '=', 'sp.id')
            ->where('c.tenant_id', $tenantId)
            ->where('c.status', 'active')
            ->selectRaw('sp.name, sp.carrier, sp.technology, COUNT(*) AS cnt, SUM(c.monthly_price) AS mrr')
            ->groupBy('sp.id', 'sp.name', 'sp.carrier', 'sp.technology')
            ->orderByDesc('cnt')
            ->get();

        // Per carrier
        $byCarrier = DB::table('contracts')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->selectRaw('carrier, COUNT(*) AS cnt')
            ->groupBy('carrier')
            ->pluck('cnt', 'carrier');

        // Per status
        $byStatus = DB::table('contracts')
            ->where('tenant_id', $tenantId)
            ->selectRaw('status, COUNT(*) AS cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $years = range(now()->year, max(now()->year - 4, 2024));

        return view('reporting.contracts', compact('monthly', 'churnByMonth', 'byPlan', 'byCarrier', 'byStatus', 'year', 'years'));
    }

    // ── Agenti / Provvigioni ──────────────────────────────────────────────────

    public function agents(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year     = (int) $request->input('year', now()->year);
        $months   = range(1, 12);

        // Top 10 agenti per provvigioni nel periodo
        $topAgents = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('commission_entries as ce', 'ce.agent_id', '=', 'a.id')
            ->where('u.tenant_id', $tenantId)
            ->whereYear('ce.period_month', $year)
            ->selectRaw("a.id, a.code, a.business_name, a.commission_rate,
                SUM(ce.amount_cents) AS total_cents,
                SUM(CASE WHEN ce.status='paid'    THEN ce.amount_cents ELSE 0 END) AS paid_cents,
                SUM(CASE WHEN ce.status='pending' THEN ce.amount_cents ELSE 0 END) AS pending_cents,
                COUNT(DISTINCT ce.contract_id) AS contracts_count")
            ->groupBy('a.id', 'a.code', 'a.business_name', 'a.commission_rate')
            ->orderByDesc('total_cents')
            ->limit(10)
            ->get();

        // Totali globali per status
        $globalTotals = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('commission_entries as ce', 'ce.agent_id', '=', 'a.id')
            ->where('u.tenant_id', $tenantId)
            ->whereYear('ce.period_month', $year)
            ->selectRaw("
                SUM(ce.amount_cents) AS total_cents,
                SUM(CASE WHEN ce.status='pending' THEN ce.amount_cents ELSE 0 END) AS pending_cents,
                SUM(CASE WHEN ce.status='accrued' THEN ce.amount_cents ELSE 0 END) AS accrued_cents,
                SUM(CASE WHEN ce.status='paid'    THEN ce.amount_cents ELSE 0 END) AS paid_cents,
                COUNT(DISTINCT a.id) AS agents_count")
            ->first();

        // Andamento mensile provvigioni (chart)
        $monthlyTrend = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('commission_entries as ce', 'ce.agent_id', '=', 'a.id')
            ->where('u.tenant_id', $tenantId)
            ->whereYear('ce.period_month', $year)
            ->selectRaw("EXTRACT(MONTH FROM ce.period_month)::int AS month,
                SUM(ce.amount_cents) AS total_cents,
                SUM(CASE WHEN ce.status='paid' THEN ce.amount_cents ELSE 0 END) AS paid_cents")
            ->groupByRaw("EXTRACT(MONTH FROM ce.period_month)::int")
            ->orderByRaw("EXTRACT(MONTH FROM ce.period_month)::int")
            ->get()->keyBy('month');

        // Liquidazioni per stato
        $liquidations = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('commission_liquidations as cl', 'cl.agent_id', '=', 'a.id')
            ->where('u.tenant_id', $tenantId)
            ->whereYear('cl.period_month', $year)
            ->selectRaw("cl.id, cl.status, cl.period_month, cl.total_amount_cents,
                a.business_name, a.code, cl.approved_at, cl.paid_at")
            ->orderByDesc('cl.period_month')
            ->limit(20)
            ->get();

        // Liquidazioni per stato (contatori)
        $liquidationStats = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('commission_liquidations as cl', 'cl.agent_id', '=', 'a.id')
            ->where('u.tenant_id', $tenantId)
            ->whereYear('cl.period_month', $year)
            ->selectRaw("cl.status, COUNT(*) AS cnt, SUM(cl.total_amount_cents) AS total_cents")
            ->groupBy('cl.status')
            ->get()->keyBy('status');

        // Agenti attivi totali nel tenant
        $activeAgentsCount = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('u.tenant_id', $tenantId)
            ->where('a.status', 'active')
            ->count();

        $years = range(now()->year, max(now()->year - 3, 2024));

        return view('reporting.agents', compact(
            'topAgents', 'globalTotals', 'monthlyTrend', 'months',
            'liquidations', 'liquidationStats', 'activeAgentsCount', 'year', 'years'
        ));
    }

    // ── Ticket SLA ────────────────────────────────────────────────────────────

    public function tickets(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $days     = (int) $request->input('days', 30);
        $from     = now()->subDays($days);

        // Per priorità
        $byPriority = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->where('opened_at', '>=', $from)
            ->selectRaw('priority, COUNT(*) AS total,
                SUM(CASE WHEN status IN (\'resolved\',\'closed\') THEN 1 ELSE 0 END) AS resolved,
                AVG(CASE WHEN resolved_at IS NOT NULL
                    THEN EXTRACT(EPOCH FROM (resolved_at - opened_at))/3600
                    ELSE NULL END) AS avg_hours')
            ->groupBy('priority')
            ->get();

        // SLA compliance per priorità
        $slaByPriority = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->where('opened_at', '>=', $from)
            ->whereNotNull('resolved_at')
            ->whereNotNull('due_at')
            ->selectRaw("priority,
                COUNT(*) AS total,
                SUM(CASE WHEN resolved_at <= due_at THEN 1 ELSE 0 END) AS within_sla")
            ->groupBy('priority')
            ->get();

        // Trend giornaliero ultimi N giorni
        $trend = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->where('opened_at', '>=', $from)
            ->selectRaw("DATE(opened_at) AS day, COUNT(*) AS opened,
                SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) AS closed_same_day")
            ->groupByRaw('DATE(opened_at)')
            ->orderBy('day')
            ->get();

        // Tecnici con più ticket assegnati
        $byTechnician = DB::table('trouble_tickets as t')
            ->join('users as u', 't.assigned_to', '=', 'u.id')
            ->where('t.tenant_id', $tenantId)
            ->where('t.opened_at', '>=', $from)
            ->selectRaw("u.name,
                COUNT(*) AS total,
                SUM(CASE WHEN t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved,
                AVG(CASE WHEN t.resolved_at IS NOT NULL
                    THEN EXTRACT(EPOCH FROM (t.resolved_at - t.opened_at))/3600
                    ELSE NULL END) AS avg_hours")
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Per tipo
        $byType = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->where('opened_at', '>=', $from)
            ->selectRaw('type, COUNT(*) AS cnt')
            ->groupBy('type')
            ->pluck('cnt', 'type');

        return view('reporting.tickets', compact('byPriority', 'slaByPriority', 'trend', 'byTechnician', 'byType', 'days'));
    }
}
