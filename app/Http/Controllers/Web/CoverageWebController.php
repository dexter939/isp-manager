<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoverageWebController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $stats = [
            'openfiber' => DB::table('coverage_openfiber')->where('tenant_id', $tenantId)->count(),
            'fibercop'  => DB::table('coverage_fibercop')->where('tenant_id', $tenantId)->count(),
            'addresses' => DB::table('address_registry')->where('tenant_id', $tenantId)->count(),
        ];

        $imports = DB::table('coverage_import_logs')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('coverage.index', compact('stats', 'imports'));
    }

    public function feasibility(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $stats = [
            'openfiber' => DB::table('coverage_openfiber')->where('tenant_id', $tenantId)->count(),
            'fibercop'  => DB::table('coverage_fibercop')->where('tenant_id', $tenantId)->count(),
            'addresses' => DB::table('address_registry')->where('tenant_id', $tenantId)->count(),
        ];

        $imports = DB::table('coverage_import_logs')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $feasibilityResult = null;

        if ($request->filled('address')) {
            $service           = app(\Modules\Coverage\Services\FeasibilityService::class);
            $feasibilityResult = $service->check(
                address: $request->input('address'),
                cap:     $request->input('cap'),
                carrier: $request->input('carrier') ?: null,
            );
        }

        return view('coverage.index', compact('stats', 'imports', 'feasibilityResult'));
    }

    public function elevationIndex()
    {
        $tenantId = auth()->user()->tenant_id;

        $sites = DB::table('network_sites')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'altitude_meters']);

        $recentProfiles = DB::table('elevation_profiles as ep')
            ->leftJoin('network_sites as ns', 'ep.network_site_id', '=', 'ns.id')
            ->where('ep.tenant_id', $tenantId)
            ->selectRaw('ep.*, ns.name AS site_name')
            ->orderByDesc('ep.calculated_at')
            ->limit(20)
            ->get();

        return view('coverage.elevation', compact('sites', 'recentProfiles'));
    }

    public function elevationCalculate(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'network_site_id'  => ['required', 'uuid'],
            'customer_lat'     => ['required', 'numeric', 'between:-90,90'],
            'customer_lon'     => ['required', 'numeric', 'between:-180,180'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'antenna_height_m' => ['required', 'integer', 'min:1', 'max:100'],
            'cpe_height_m'     => ['required', 'integer', 'min:1', 'max:30'],
            'frequency_ghz'    => ['nullable', 'numeric', 'min:0.1', 'max:100'],
        ]);

        $site = DB::table('network_sites')
            ->where('id', $data['network_site_id'])
            ->where('tenant_id', $tenantId)
            ->first();
        if (!$site) abort(404);

        try {
            $service = app(\Modules\Coverage\Services\ElevationProfileService::class);
            $profile = $service->calculate(
                site:           $site,
                customerLat:    (float) $data['customer_lat'],
                customerLon:    (float) $data['customer_lon'],
                antennaHeight:  (int) $data['antenna_height_m'],
                cpeHeight:      (int) $data['cpe_height_m'],
                frequencyGhz:   isset($data['frequency_ghz']) ? (float) $data['frequency_ghz'] : null,
                customerAddress: $data['customer_address'] ?? null,
                tenantId:       $tenantId,
            );

            return redirect()->route('coverage.elevation.index')
                ->with('success', "Profilo calcolato: {$profile->distance_km} km, ostruzione: " . ($profile->has_obstruction ? 'Sì' : 'No'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore calcolo elevazione: ' . $e->getMessage())->withInput();
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'    => 'required|file|mimes:csv,xlsx',
            'carrier' => 'required|in:openfiber,fibercop',
        ]);

        $tenantId = auth()->user()->tenant_id;

        dispatch(new \Modules\Coverage\Jobs\ImportCoverageJob(
            filePath: $request->file('file')->store('coverage-imports'),
            carrier:  $request->input('carrier'),
            tenantId: $tenantId,
        ));

        return back()->with('success', 'Import avviato in background.');
    }
}
