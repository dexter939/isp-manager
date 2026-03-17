<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetworkWebController extends Controller
{
    public function radius()
    {
        $tenantId = auth()->user()->tenant_id;

        $stats = [
            'active_sessions' => DB::table('radius_sessions')
                ->where('tenant_id', $tenantId)
                ->whereNull('acct_stop')
                ->count(),

            'radius_users' => DB::table('radius_users')
                ->where('tenant_id', $tenantId)
                ->count(),

            'walled_garden' => DB::table('radius_users')
                ->where('tenant_id', $tenantId)
                ->where('is_walled', true)
                ->count(),

            'coa_sent' => DB::table('coa_requests')
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', today())
                ->count(),
        ];

        $activeSessions = DB::table('radius_sessions')
            ->where('tenant_id', $tenantId)
            ->whereNull('acct_stop')
            ->orderByDesc('acct_start_time')
            ->paginate(50);

        return view('network.radius', compact('stats', 'activeSessions'));
    }

    // ── Network Sites ─────────────────────────────────────────────────────────

    public function sitesIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('network_sites')->where('tenant_id', $tenantId);

        if ($search = $request->input('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%']);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sites = $query->orderBy('name')->paginate(25)->withQueryString();

        $stats = [
            'total'   => DB::table('network_sites')->where('tenant_id', $tenantId)->count(),
            'online'  => DB::table('network_sites')->where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'offline' => DB::table('network_sites')->where('tenant_id', $tenantId)->whereIn('status', ['inactive', 'maintenance'])->count(),
        ];

        return view('network.sites.index', compact('sites', 'stats'));
    }

    public function sitesShow(string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $site     = DB::table('network_sites')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $hardware = DB::table('network_site_hardware as nsh')
            ->join('hardware_devices as hd', 'nsh.hardware_device_id', '=', 'hd.id')
            ->where('nsh.network_site_id', $id)
            ->selectRaw('hd.*')
            ->get();

        $customerServices = DB::table('network_site_customer_services as nscs')
            ->join('contracts', 'nscs.contract_id', '=', 'contracts.id')
            ->join('customers', 'contracts.customer_id', '=', 'customers.id')
            ->where('nscs.network_site_id', $id)
            ->selectRaw("nscs.*, contracts.id AS contract_id,
                COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS customer_full_name")
            ->get();

        return view('network.sites.show', compact('site', 'hardware', 'customerServices'));
    }

    // ── Topology ──────────────────────────────────────────────────────────────

    public function topologyIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $sites = DB::table('network_sites')->where('tenant_id', $tenantId)->orderBy('name')->get();

        $links = DB::table('topology_links as tl')
            ->leftJoin('hardware_devices as a', 'tl.device_a_id', '=', 'a.id')
            ->leftJoin('hardware_devices as b', 'tl.device_b_id', '=', 'b.id')
            ->where('tl.tenant_id', $tenantId)
            ->selectRaw('tl.*, a.hostname AS device_a_hostname, b.hostname AS device_b_hostname')
            ->paginate(50)->withQueryString();

        // Graph data for vis-network
        $devices = DB::table('hardware_devices')->where('tenant_id', $tenantId)->get();
        $graphData = [
            'nodes' => $devices->map(fn ($d) => ['id' => $d->id, 'label' => $d->hostname, 'title' => $d->ip_address])->values(),
            'edges' => DB::table('topology_links')->where('tenant_id', $tenantId)
                ->get()->map(fn ($l) => ['from' => $l->device_a_id, 'to' => $l->device_b_id])->values(),
        ];

        return view('network.topology', compact('sites', 'links', 'graphData'));
    }

    public function topologyLinkStore(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'device_a_id'      => ['required', 'uuid'],
            'device_b_id'      => ['required', 'uuid', 'different:device_a_id'],
            'link_type'        => ['required', 'in:fiber,radio,copper,uplink,aggregate,other'],
            'bandwidth_mbps'   => ['nullable', 'integer', 'min:1'],
            'source_interface' => ['nullable', 'string', 'max:50'],
            'target_interface' => ['nullable', 'string', 'max:50'],
            'description'      => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('topology_links')->insert(array_merge($data, [
            'id'         => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'  => $tenantId,
            'status'     => 'unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Link topologico aggiunto.');
    }

    public function topologyLinkDestroy(string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        DB::table('topology_links')->where('id', $id)->where('tenant_id', $tenantId)->delete();
        return back()->with('success', 'Link eliminato.');
    }

    public function topologyDiscoveryRun()
    {
        try {
            \Illuminate\Support\Facades\Artisan::queue('topology:discover');
        } catch (\Throwable) {
            // job dispatched via queue if artisan not available
        }
        return back()->with('success', 'Scoperta topologica avviata. I candidati appariranno nella lista quando completata.');
    }

    public function topologyDiscoveryConfirm(string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $candidate = DB::table('topology_discovery_candidates as tdc')
            ->join('topology_discovery_runs as tdr', 'tdc.discovery_run_id', '=', 'tdr.id')
            ->where('tdc.id', $id)
            ->where('tdr.tenant_id', $tenantId)
            ->select('tdc.*')
            ->first();

        if (!$candidate) abort(404);

        // Create topology link from candidate
        if ($candidate->matched_device_id) {
            DB::table('topology_links')->insert([
                'id'               => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id'        => $tenantId,
                'device_a_id'      => $candidate->source_device_id,
                'device_b_id'      => $candidate->matched_device_id,
                'link_type'        => 'other',
                'source_interface' => $candidate->source_interface,
                'target_interface' => $candidate->target_interface,
                'status'           => 'unknown',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        DB::table('topology_discovery_candidates')->where('id', $id)->update(['status' => 'confirmed']);
        return back()->with('success', 'Candidato confermato e link creato.');
    }

    public function topologyDiscoveryReject(string $id)
    {
        DB::table('topology_discovery_candidates')->where('id', $id)->update(['status' => 'rejected']);
        return back()->with('success', 'Candidato rifiutato.');
    }

    // ── Fair Usage ────────────────────────────────────────────────────────────

    public function fairUsageIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('customer_traffic_usage as ctu')
            ->leftJoin('pppoe_accounts as ppa', 'ctu.pppoe_account_id', '=', 'ppa.id')
            ->leftJoin('contracts', 'ppa.contract_id', '=', 'contracts.id')
            ->leftJoin('customers', 'contracts.customer_id', '=', 'customers.id')
            ->where('ctu.tenant_id', $tenantId)
            ->selectRaw("ctu.*,
                COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS customer_full_name");

        if ($fup = $request->input('fup_status')) {
            $query->where('ctu.fup_status', $fup);
        }
        if ($account = $request->input('pppoe_account')) {
            $query->whereRaw('LOWER(ctu.pppoe_account_id::text) LIKE ?', ['%'.strtolower($account).'%']);
        }

        $usages = $query->orderBy('ctu.bytes_total', 'desc')->paginate(50)->withQueryString();

        $products = DB::table('fup_topup_products')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('data_gb_added')
            ->get();

        $stats = [
            'normal'     => DB::table('customer_traffic_usage')->where('tenant_id', $tenantId)->where('fup_status', 'normal')->count(),
            'throttling' => DB::table('customer_traffic_usage')->where('tenant_id', $tenantId)->whereIn('fup_status', ['throttled', 'warning'])->count(),
            'exhausted'  => DB::table('customer_traffic_usage')->where('tenant_id', $tenantId)->where('fup_status', 'exhausted')->count(),
        ];

        return view('network.fair-usage', compact('usages', 'products', 'stats'));
    }
}
