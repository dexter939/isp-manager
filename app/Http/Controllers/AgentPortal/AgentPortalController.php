<?php

declare(strict_types=1);

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AgentPortalController extends Controller
{
    private function agent()
    {
        return auth('agent')->user();
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $agentId = $this->agent()->id;

        // KPI: Contratti assegnati
        $contractsCount = DB::table('agent_contract_assignments')
            ->where('agent_id', $agentId)
            ->count();

        // KPI: Provvigioni per stato
        $commissionsByStatus = DB::table('commission_entries')
            ->where('agent_id', $agentId)
            ->selectRaw("status, SUM(amount_cents) as total, COUNT(*) as count")
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pendingCents = $commissionsByStatus['pending']?->total ?? 0;
        $accruedCents = $commissionsByStatus['accrued']?->total ?? 0;
        $paidCents    = $commissionsByStatus['paid']?->total ?? 0;

        // KPI: Liquidazioni
        $lastLiquidation = DB::table('commission_liquidations')
            ->where('agent_id', $agentId)
            ->orderByDesc('period_month')
            ->first();

        // Ultimi 5 contratti assegnati
        $recentContracts = DB::table('agent_contract_assignments as aca')
            ->where('aca.agent_id', $agentId)
            ->join('contracts as c', 'c.id', '=', 'aca.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->leftJoin('service_plans as sp', 'sp.id', '=', 'c.service_plan_id')
            ->select([
                'c.id',
                'c.contract_number',
                'c.status',
                'c.activation_date',
                'cu.ragione_sociale',
                'cu.nome',
                'cu.cognome',
                'sp.name as plan_name',
                'aca.assigned_at',
            ])
            ->orderByDesc('aca.assigned_at')
            ->limit(5)
            ->get();

        // Ultimi 5 movimenti provvigioni
        $recentCommissions = DB::table('commission_entries as ce')
            ->where('ce.agent_id', $agentId)
            ->join('contracts as c', 'c.id', '=', 'ce.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->select([
                'ce.id',
                'ce.amount_cents',
                'ce.status',
                'ce.period_month',
                'c.contract_number',
                'cu.ragione_sociale',
                'cu.nome',
                'cu.cognome',
            ])
            ->orderByDesc('ce.period_month')
            ->orderByDesc('ce.id')
            ->limit(5)
            ->get();

        return view('agent-portal.dashboard', compact(
            'contractsCount', 'pendingCents', 'accruedCents', 'paidCents',
            'lastLiquidation', 'recentContracts', 'recentCommissions'
        ));
    }

    // ── Contracts ─────────────────────────────────────────────────────────────

    public function contracts(Request $request)
    {
        $agentId = $this->agent()->id;

        $query = DB::table('agent_contract_assignments as aca')
            ->where('aca.agent_id', $agentId)
            ->join('contracts as c', 'c.id', '=', 'aca.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->leftJoin('service_plans as sp', 'sp.id', '=', 'c.service_plan_id')
            ->select([
                'c.id',
                'c.contract_number',
                'c.status',
                'c.activation_date',
                'c.termination_date',
                'cu.ragione_sociale',
                'cu.nome',
                'cu.cognome',
                'cu.email as customer_email',
                'sp.name as plan_name',
                'sp.price_cents',
                'aca.assigned_at',
            ]);

        if ($status = $request->input('status')) {
            $query->where('c.status', $status);
        }

        $contracts = $query->orderByDesc('aca.assigned_at')->paginate(20);

        return view('agent-portal.contracts.index', compact('contracts'));
    }

    // ── Commissions ───────────────────────────────────────────────────────────

    public function commissions(Request $request)
    {
        $agentId = $this->agent()->id;

        $query = DB::table('commission_entries as ce')
            ->where('ce.agent_id', $agentId)
            ->join('contracts as c', 'c.id', '=', 'ce.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->leftJoin('commission_rules as cr', 'cr.id', '=', 'ce.rule_id')
            ->select([
                'ce.id',
                'ce.amount_cents',
                'ce.status',
                'ce.period_month',
                'ce.liquidation_id',
                'c.contract_number',
                'cu.ragione_sociale',
                'cu.nome',
                'cu.cognome',
                'cr.offer_type',
                'cr.rate_type',
            ]);

        if ($status = $request->input('status')) {
            $query->where('ce.status', $status);
        }

        if ($period = $request->input('period')) {
            $query->whereRaw("DATE_TRUNC('month', ce.period_month) = ?", [$period . '-01']);
        }

        // Totali per periodo (ultimi 6 mesi) per grafico
        $monthlyTotals = DB::table('commission_entries')
            ->where('agent_id', $agentId)
            ->whereIn('status', ['accrued', 'paid'])
            ->selectRaw("TO_CHAR(DATE_TRUNC('month', period_month), 'YYYY-MM') as month, SUM(amount_cents) as total")
            ->groupByRaw("DATE_TRUNC('month', period_month)")
            ->orderByRaw("DATE_TRUNC('month', period_month)")
            ->limit(6)
            ->get();

        $commissions = $query->orderByDesc('ce.period_month')->orderByDesc('ce.id')->paginate(20);

        return view('agent-portal.commissions.index', compact('commissions', 'monthlyTotals'));
    }

    // ── Liquidations ──────────────────────────────────────────────────────────

    public function liquidations()
    {
        $agentId = $this->agent()->id;

        $liquidations = DB::table('commission_liquidations')
            ->where('agent_id', $agentId)
            ->orderByDesc('period_month')
            ->paginate(20);

        // Totale incassato
        $totalPaidCents = DB::table('commission_liquidations')
            ->where('agent_id', $agentId)
            ->where('status', 'paid')
            ->sum('total_amount_cents');

        return view('agent-portal.liquidations.index', compact('liquidations', 'totalPaidCents'));
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    public function profile()
    {
        $agent = $this->agent();
        return view('agent-portal.profile', compact('agent'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $agent = $this->agent();

        if (!Hash::check($request->input('current_password'), $agent->portal_password)) {
            return back()->withErrors(['current_password' => 'La password attuale non è corretta.']);
        }

        DB::table('agents')
            ->where('id', $agent->id)
            ->update(['portal_password' => Hash::make($request->input('password'))]);

        return back()->with('success', 'Password aggiornata con successo.');
    }
}
