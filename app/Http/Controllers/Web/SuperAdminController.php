<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->is_super_admin) {
                abort(403, 'Accesso riservato ai super-amministratori.');
            }
            return $next($request);
        })->except('stopImpersonating');

        // stopImpersonating is accessible to impersonated users too
        $this->middleware(function ($request, $next) {
            if (!session('superadmin_original_id')) {
                abort(403);
            }
            return $next($request);
        })->only('stopImpersonating');
    }

    // ── Tenant list ───────────────────────────────────────────────────────────

    public function index()
    {
        $tenants = DB::table('tenants')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // Enrich with stats
        $tenantIds = $tenants->pluck('id');

        $userCounts = DB::table('users')
            ->whereIn('tenant_id', $tenantIds)
            ->whereNull('deleted_at')
            ->selectRaw('tenant_id, COUNT(*) AS cnt')
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        $contractStats = DB::table('contracts')
            ->whereIn('tenant_id', $tenantIds)
            ->selectRaw('tenant_id, COUNT(*) AS total,
                SUM(CASE WHEN status=\'active\' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status=\'active\' THEN monthly_price ELSE 0 END) AS mrr')
            ->groupBy('tenant_id')
            ->get()->keyBy('tenant_id');

        $tenants = $tenants->map(function ($t) use ($userCounts, $contractStats) {
            $t->user_count       = $userCounts[$t->id] ?? 0;
            $t->contract_count   = $contractStats[$t->id]->total ?? 0;
            $t->active_contracts = $contractStats[$t->id]->active ?? 0;
            $t->mrr              = $contractStats[$t->id]->mrr ?? 0;
            return $t;
        });

        return view('superadmin.tenants.index', compact('tenants'));
    }

    // ── Tenant create ─────────────────────────────────────────────────────────

    public function create()
    {
        return view('superadmin.tenants.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => 'required|string|max:100|alpha_dash|unique:tenants,slug',
            'domain'         => 'nullable|string|max:255|unique:tenants,domain',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        $tenantId = DB::table('tenants')->insertGetId([
            'name'       => $request->input('name'),
            'slug'       => $request->input('slug'),
            'domain'     => $request->input('domain'),
            'settings'   => json_encode([]),
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'tenant_id'         => $tenantId,
            'name'              => $request->input('admin_name'),
            'email'             => $request->input('admin_email'),
            'password'          => Hash::make($request->input('admin_password')),
            'roles'             => json_encode(['admin']),
            'is_active'         => true,
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return redirect()->route('superadmin.tenants.show', $tenantId)
            ->with('success', "Tenant \"{$request->input('name')}\" creato con successo.");
    }

    // ── Tenant show ───────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        $stats = [
            'users'            => DB::table('users')->where('tenant_id', $id)->whereNull('deleted_at')->count(),
            'contracts_active' => DB::table('contracts')->where('tenant_id', $id)->where('status', 'active')->count(),
            'contracts_total'  => DB::table('contracts')->where('tenant_id', $id)->count(),
            'mrr'              => DB::table('contracts')->where('tenant_id', $id)->where('status', 'active')->sum('monthly_price'),
            'invoices_total'   => DB::table('invoices')->where('tenant_id', $id)->count(),
            'overdue_amount'   => DB::table('invoices')->where('tenant_id', $id)->where('status', 'overdue')->sum('total'),
            'tickets_open'     => DB::table('trouble_tickets')->where('tenant_id', $id)->whereIn('status', ['open', 'in_progress'])->count(),
            'cpe_total'        => DB::table('cpe_devices')->where('tenant_id', $id)->count(),
        ];

        $users = DB::table('users')
            ->where('tenant_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $revenueChart = DB::table('invoices')
            ->where('tenant_id', $id)
            ->whereIn('status', ['paid', 'issued', 'overdue'])
            ->where('issue_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("TO_CHAR(issue_date, 'YYYY-MM') AS month, SUM(total) AS revenue")
            ->groupByRaw("TO_CHAR(issue_date, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(issue_date, 'YYYY-MM')")
            ->get();

        return view('superadmin.tenants.show', compact('tenant', 'stats', 'users', 'revenueChart'));
    }

    // ── Tenant edit ───────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        return view('superadmin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, int $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|max:100|alpha_dash|unique:tenants,slug,' . $id,
            'domain' => 'nullable|string|max:255|unique:tenants,domain,' . $id,
        ]);

        DB::table('tenants')->where('id', $id)->update([
            'name'       => $request->input('name'),
            'slug'       => $request->input('slug'),
            'domain'     => $request->input('domain'),
            'updated_at' => now(),
        ]);

        return redirect()->route('superadmin.tenants.show', $id)
            ->with('success', 'Tenant aggiornato.');
    }

    public function toggleActive(int $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        DB::table('tenants')->where('id', $id)->update([
            'is_active'  => !$tenant->is_active,
            'updated_at' => now(),
        ]);

        $status = $tenant->is_active ? 'sospeso' : 'riattivato';
        return back()->with('success', "Tenant {$status}.");
    }

    // ── Impersonation ─────────────────────────────────────────────────────────

    public function impersonate(int $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->where('is_active', true)->whereNull('deleted_at')->firstOrFail();

        $targetUser = \App\Models\User::where('tenant_id', $id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderByRaw("(roles::jsonb @> '\"admin\"'::jsonb)::int DESC")
            ->first();

        if (!$targetUser) {
            return back()->with('error', 'Nessun utente attivo trovato per questo tenant.');
        }

        session(['superadmin_original_id' => auth()->id()]);

        Auth::login($targetUser);

        return redirect()->route('dashboard')
            ->with('success', "Stai navigando come {$targetUser->name} ({$tenant->name}).");
    }

    public function stopImpersonating()
    {
        $originalId = session()->pull('superadmin_original_id');

        $superAdmin = \App\Models\User::find($originalId);

        if (!$superAdmin || !$superAdmin->is_super_admin) {
            Auth::logout();
            return redirect()->route('login');
        }

        Auth::login($superAdmin);

        return redirect()->route('superadmin.tenants.index')
            ->with('success', 'Impersonazione terminata.');
    }
}
