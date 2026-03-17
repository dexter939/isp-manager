<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentWebController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('u.tenant_id', $tenantId)
            ->select([
                'a.id',
                'a.code',
                'a.business_name',
                'a.status',
                'a.commission_rate',
                'a.portal_email',
                'a.portal_last_login_at',
                'u.name as user_name',
                'u.email as user_email',
                DB::raw('(SELECT COUNT(*) FROM agent_contract_assignments aca WHERE aca.agent_id = a.id) as contracts_count'),
                DB::raw('(SELECT COALESCE(SUM(ce.amount_cents),0) FROM commission_entries ce WHERE ce.agent_id = a.id AND ce.status IN (\'accrued\',\'paid\')) as total_commissions_cents'),
            ]);

        if ($status = $request->input('status')) {
            $query->where('a.status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('a.business_name', 'ilike', "%{$search}%")
                  ->orWhere('a.code', 'ilike', "%{$search}%")
                  ->orWhere('u.email', 'ilike', "%{$search}%");
            });
        }

        $agents = $query->orderBy('a.business_name')->paginate(20)->withQueryString();

        return view('admin.agents.index', compact('agents'));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create()
    {
        $tenantId = auth()->user()->tenant_id;

        $users = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('agents')->whereColumn('agents.user_id', 'users.id');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.agents.create', compact('users'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'user_id'         => 'required|integer',
            'business_name'   => 'required|string|max:255',
            'codice_fiscale'  => 'required|string|max:16',
            'iban'            => 'required|string|max:34',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'piva'            => 'nullable|string|max:11',
            'parent_agent_id' => 'nullable|integer',
            'portal_email'    => 'nullable|email|max:255|unique:agents,portal_email',
        ]);

        // Verify user belongs to this tenant
        $user = DB::table('users')
            ->where('id', $request->integer('user_id'))
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$user) {
            return back()->withErrors(['user_id' => 'Utente non valido.'])->withInput();
        }

        // Auto-generate agent code
        $count = DB::table('agents')
            ->join('users', 'users.id', '=', 'agents.user_id')
            ->where('users.tenant_id', $tenantId)
            ->count();
        $code = 'AGT-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        $data = [
            'uuid'            => Str::uuid(),
            'user_id'         => $request->integer('user_id'),
            'business_name'   => $request->input('business_name'),
            'codice_fiscale'  => $request->input('codice_fiscale'),
            'iban'            => $request->input('iban'),
            'commission_rate' => $request->input('commission_rate'),
            'piva'            => $request->input('piva'),
            'parent_agent_id' => $request->input('parent_agent_id') ?: null,
            'code'            => $code,
            'status'          => 'active',
            'created_at'      => now(),
            'updated_at'      => now(),
        ];

        if ($portalEmail = $request->input('portal_email')) {
            $data['portal_email']    = $portalEmail;
            $data['portal_password'] = Hash::make(Str::random(16)); // temp, force reset
        }

        $agentId = DB::table('agents')->insertGetId($data);

        return redirect()->route('admin.agents.show', $agentId)
            ->with('success', "Agente «{$request->input('business_name')}» creato.");
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $agent    = $this->findAgent($id, $tenantId);

        // Contratti assegnati
        $contracts = DB::table('agent_contract_assignments as aca')
            ->where('aca.agent_id', $id)
            ->join('contracts as c', 'c.id', '=', 'aca.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->leftJoin('service_plans as sp', 'sp.id', '=', 'c.service_plan_id')
            ->select([
                'c.id', 'c.contract_number', 'c.status',
                'cu.ragione_sociale', 'cu.nome', 'cu.cognome',
                'sp.name as plan_name', 'sp.price_cents',
                'aca.assigned_at',
            ])
            ->orderByDesc('aca.assigned_at')
            ->limit(20)
            ->get();

        // Ultimi movimenti provvigioni
        $commissions = DB::table('commission_entries as ce')
            ->where('ce.agent_id', $id)
            ->join('contracts as c', 'c.id', '=', 'ce.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->select([
                'ce.id', 'ce.amount_cents', 'ce.status',
                'ce.period_month', 'ce.liquidation_id',
                'c.contract_number',
                'cu.ragione_sociale', 'cu.nome', 'cu.cognome',
            ])
            ->orderByDesc('ce.period_month')
            ->limit(20)
            ->get();

        // Liquidazioni
        $liquidations = DB::table('commission_liquidations')
            ->where('agent_id', $id)
            ->orderByDesc('period_month')
            ->limit(12)
            ->get();

        // KPIs
        $kpis = DB::table('commission_entries')
            ->where('agent_id', $id)
            ->selectRaw("
                COUNT(*) as total_entries,
                COALESCE(SUM(CASE WHEN status='pending'  THEN amount_cents END), 0) as pending_cents,
                COALESCE(SUM(CASE WHEN status='accrued'  THEN amount_cents END), 0) as accrued_cents,
                COALESCE(SUM(CASE WHEN status='paid'     THEN amount_cents END), 0) as paid_cents
            ")
            ->first();

        return view('admin.agents.show', compact('agent', 'contracts', 'commissions', 'liquidations', 'kpis'));
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $agent    = $this->findAgent($id, $tenantId);

        $parentAgents = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('u.tenant_id', $tenantId)
            ->where('a.id', '!=', $id)
            ->where('a.status', 'active')
            ->orderBy('a.business_name')
            ->get(['a.id', 'a.business_name', 'a.code']);

        return view('admin.agents.edit', compact('agent', 'parentAgents'));
    }

    public function update(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $this->findAgent($id, $tenantId);

        $request->validate([
            'business_name'   => 'required|string|max:255',
            'codice_fiscale'  => 'required|string|max:16',
            'iban'            => 'required|string|max:34',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'piva'            => 'nullable|string|max:11',
            'parent_agent_id' => 'nullable|integer',
            'status'          => 'required|in:active,inactive,suspended',
            'portal_email'    => "nullable|email|max:255|unique:agents,portal_email,{$id}",
        ]);

        DB::table('agents')->where('id', $id)->update([
            'business_name'   => $request->input('business_name'),
            'codice_fiscale'  => $request->input('codice_fiscale'),
            'iban'            => $request->input('iban'),
            'commission_rate' => $request->input('commission_rate'),
            'piva'            => $request->input('piva'),
            'parent_agent_id' => $request->input('parent_agent_id') ?: null,
            'status'          => $request->input('status'),
            'portal_email'    => $request->input('portal_email') ?: null,
            'updated_at'      => now(),
        ]);

        return redirect()->route('admin.agents.show', $id)
            ->with('success', 'Agente aggiornato.');
    }

    // ── Reset portal password ─────────────────────────────────────────────────

    public function resetPortalPassword(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $agent    = $this->findAgent($id, $tenantId);

        $request->validate([
            'portal_password' => 'required|string|min:8|confirmed',
        ]);

        DB::table('agents')->where('id', $id)->update([
            'portal_password' => Hash::make($request->input('portal_password')),
            'updated_at'      => now(),
        ]);

        return back()->with('success', "Password portale per «{$agent->business_name}» aggiornata.");
    }

    // ── Approve / Pay liquidation ─────────────────────────────────────────────

    public function approveLiquidation(int $agentId, int $liquidationId)
    {
        $tenantId = auth()->user()->tenant_id;
        $this->findAgent($agentId, $tenantId);

        DB::table('commission_liquidations')
            ->where('id', $liquidationId)
            ->where('agent_id', $agentId)
            ->where('status', 'draft')
            ->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'updated_at'  => now(),
            ]);

        return back()->with('success', 'Liquidazione approvata.');
    }

    public function payLiquidation(int $agentId, int $liquidationId)
    {
        $tenantId = auth()->user()->tenant_id;
        $this->findAgent($agentId, $tenantId);

        DB::table('commission_liquidations')
            ->where('id', $liquidationId)
            ->where('agent_id', $agentId)
            ->where('status', 'approved')
            ->update([
                'status'     => 'paid',
                'paid_at'    => now(),
                'updated_at' => now(),
            ]);

        // Mark related entries as paid
        DB::table('commission_entries')
            ->where('liquidation_id', $liquidationId)
            ->update(['status' => 'paid', 'updated_at' => now()]);

        return back()->with('success', 'Liquidazione marcata come pagata.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findAgent(int $id, int $tenantId): object
    {
        $agent = DB::table('agents as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.id', $id)
            ->where('u.tenant_id', $tenantId)
            ->select('a.*', 'u.name as user_name', 'u.email as user_email')
            ->first();

        if (!$agent) {
            abort(404);
        }

        return $agent;
    }
}
