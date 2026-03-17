<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringWebController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $alertCounts = DB::table('network_alerts')
            ->where('tenant_id', $tenantId)
            ->whereNull('resolved_at')
            ->selectRaw('severity, count(*) AS cnt')
            ->groupBy('severity')
            ->pluck('cnt', 'severity')
            ->toArray();

        $alerts = DB::table('network_alerts')
            ->where('tenant_id', $tenantId)
            ->whereNull('resolved_at')
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $btsStations = DB::table('bts_stations')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $btsCount = $btsStations->where('is_active', true)->count();

        return view('monitoring.index', compact('alertCounts', 'alerts', 'btsStations', 'btsCount'));
    }

    public function alerts(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('monitoring_alerts as a')
            ->leftJoin('hardware_devices as d', 'a.device_id', '=', 'd.id')
            ->where('a.tenant_id', $tenantId)
            ->selectRaw("a.*, d.hostname AS device_hostname, d.ip_address AS device_ip");

        if ($state = $request->input('state')) {
            if ($state === 'resolved') {
                $query->whereNotNull('a.resolved_at');
            } elseif ($state === 'suppressed') {
                $query->where('a.suppressed', true)->whereNull('a.resolved_at');
            } else {
                $query->whereNull('a.resolved_at')->where('a.suppressed', false);
            }
        }
        if ($severity = $request->input('severity')) {
            $query->where('a.severity', $severity);
        }
        if ($device = $request->input('device')) {
            $query->where(function ($q) use ($device) {
                $q->whereRaw('LOWER(d.hostname) LIKE ?', ['%'.strtolower($device).'%'])
                  ->orWhereRaw('d.ip_address::text LIKE ?', ['%'.$device.'%']);
            });
        }

        $alerts = $query->orderByRaw("CASE a.severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->orderByDesc('a.started_at')
            ->paginate(50)->withQueryString();

        $stats = [
            'critical'       => DB::table('monitoring_alerts')->where('tenant_id', $tenantId)->where('severity', 'critical')->whereNull('resolved_at')->where('suppressed', false)->count(),
            'warning'        => DB::table('monitoring_alerts')->where('tenant_id', $tenantId)->where('severity', 'warning')->whereNull('resolved_at')->where('suppressed', false)->count(),
            'suppressed'     => DB::table('monitoring_alerts')->where('tenant_id', $tenantId)->where('suppressed', true)->whereNull('resolved_at')->count(),
            'resolved_today' => DB::table('monitoring_alerts')->where('tenant_id', $tenantId)->whereNotNull('resolved_at')->whereDate('resolved_at', today())->count(),
        ];

        return view('monitoring.alerts', compact('alerts', 'stats'));
    }

    // ── ACS / CPE ─────────────────────────────────────────────────────────────

    public function cpeIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('cpe_devices as c')
            ->leftJoin('customers as cu', 'c.customer_id', '=', 'cu.id')
            ->leftJoin('contracts as ct', 'c.contract_id', '=', 'ct.id')
            ->where('c.tenant_id', $tenantId)
            ->whereNull('c.deleted_at')
            ->selectRaw("c.*,
                COALESCE(cu.company_name, cu.first_name || ' ' || cu.last_name) AS customer_full_name,
                ct.code AS contract_code");

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(c.serial_number) LIKE ?', ['%'.strtolower($search).'%'])
                  ->orWhereRaw('LOWER(c.mac_address) LIKE ?', ['%'.strtolower($search).'%'])
                  ->orWhereRaw('LOWER(c.model) LIKE ?', ['%'.strtolower($search).'%'])
                  ->orWhereRaw('LOWER(c.tr069_id) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }
        if ($status = $request->input('status')) {
            if ($status === 'online') {
                $query->where('c.last_seen_at', '>', now()->subMinutes(15));
            } elseif ($status === 'offline') {
                $query->where(function ($q) {
                    $q->whereNull('c.last_seen_at')
                      ->orWhere('c.last_seen_at', '<=', now()->subMinutes(15));
                });
            } else {
                $query->where('c.status', $status);
            }
        }
        if ($hasAcs = $request->input('has_acs')) {
            if ($hasAcs === '1') {
                $query->whereNotNull('c.tr069_id');
            } else {
                $query->whereNull('c.tr069_id');
            }
        }

        $devices = $query->orderBy('c.serial_number')->paginate(25)->withQueryString();

        $stats = [
            'total'   => DB::table('cpe_devices')->where('tenant_id', $tenantId)->whereNull('deleted_at')->count(),
            'online'  => DB::table('cpe_devices')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('last_seen_at', '>', now()->subMinutes(15))->count(),
            'offline' => DB::table('cpe_devices')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where(function ($q) {
                $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<=', now()->subMinutes(15));
            })->count(),
            'with_acs' => DB::table('cpe_devices')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereNotNull('tr069_id')->count(),
        ];

        return view('monitoring.cpe.index', compact('devices', 'stats'));
    }

    public function cpeShow(string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $device = DB::table('cpe_devices as c')
            ->leftJoin('customers as cu', 'c.customer_id', '=', 'cu.id')
            ->leftJoin('contracts as ct', 'c.contract_id', '=', 'ct.id')
            ->where('c.id', $id)
            ->where('c.tenant_id', $tenantId)
            ->whereNull('c.deleted_at')
            ->selectRaw("c.*,
                COALESCE(cu.company_name, cu.first_name || ' ' || cu.last_name) AS customer_full_name,
                ct.code AS contract_code,
                cu.id AS customer_id")
            ->first();

        abort_if(!$device, 404);

        $tr069Params = DB::table('tr069_parameters')
            ->where('cpe_device_id', $id)
            ->orderBy('parameter_path')
            ->get();

        $recentAlerts = DB::table('monitoring_alerts')
            ->where('tenant_id', $tenantId)
            ->whereRaw("source_ref = ?", [$id])
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        return view('monitoring.cpe.show', compact('device', 'tr069Params', 'recentAlerts'));
    }
}
