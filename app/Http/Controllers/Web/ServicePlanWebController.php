<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicePlanWebController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('service_plans')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%']);
        }
        if ($carrier = $request->input('carrier')) {
            $query->where('carrier', $carrier);
        }
        if ($request->input('active_only')) {
            $query->where('is_active', true);
        }

        $plans = $query->orderBy('carrier')->orderBy('name')->paginate(25)->withQueryString();

        $carriers = DB::table('service_plans')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('carrier');

        $stats = [
            'total'    => DB::table('service_plans')->where('tenant_id', $tenantId)->whereNull('deleted_at')->count(),
            'active'   => DB::table('service_plans')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('is_active', true)->count(),
            'carriers' => $carriers->count(),
        ];

        return view('service-plans.index', compact('plans', 'carriers', 'stats'));
    }

    public function create()
    {
        return view('service-plans.create');
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'carrier'              => 'required|string|max:30',
            'technology'           => 'required|string|max:10',
            'price_monthly'        => 'required|numeric|min:0',
            'activation_fee'       => 'nullable|numeric|min:0',
            'modem_fee'            => 'nullable|numeric|min:0',
            'carrier_product_code' => 'nullable|string|max:100',
            'bandwidth_dl'         => 'required|integer|min:1',
            'bandwidth_ul'         => 'required|integer|min:1',
            'sla_type'             => 'nullable|string|max:20',
            'mtr_hours'            => 'nullable|integer|min:1',
            'min_contract_months'  => 'required|integer|min:1',
            'description'          => 'nullable|string',
            'is_active'            => 'boolean',
            'is_public'            => 'boolean',
        ]);

        $data['tenant_id']       = $tenantId;
        $data['is_active']       = $request->boolean('is_active', true);
        $data['is_public']       = $request->boolean('is_public', true);
        $data['activation_fee']  = $data['activation_fee'] ?? 0;
        $data['modem_fee']       = $data['modem_fee'] ?? 0;
        $data['technology']      = strtoupper($data['technology']);
        $data['created_at']      = now();
        $data['updated_at']      = now();

        $id = DB::table('service_plans')->insertGetId($data);

        return redirect()->route('service-plans.index')
            ->with('success', 'Piano "{$data[\'name\']}" creato con successo.');
    }

    public function edit(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $plan = DB::table('service_plans')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        return view('service-plans.edit', compact('plan'));
    }

    public function update(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('service_plans')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'carrier'              => 'required|string|max:30',
            'technology'           => 'required|string|max:10',
            'price_monthly'        => 'required|numeric|min:0',
            'activation_fee'       => 'nullable|numeric|min:0',
            'modem_fee'            => 'nullable|numeric|min:0',
            'carrier_product_code' => 'nullable|string|max:100',
            'bandwidth_dl'         => 'required|integer|min:1',
            'bandwidth_ul'         => 'required|integer|min:1',
            'sla_type'             => 'nullable|string|max:20',
            'mtr_hours'            => 'nullable|integer|min:1',
            'min_contract_months'  => 'required|integer|min:1',
            'description'          => 'nullable|string',
        ]);

        $data['is_active']  = $request->boolean('is_active');
        $data['is_public']  = $request->boolean('is_public');
        $data['technology'] = strtoupper($data['technology']);
        $data['updated_at'] = now();

        DB::table('service_plans')->where('id', $id)->update($data);

        return redirect()->route('service-plans.index')
            ->with('success', 'Piano aggiornato con successo.');
    }

    public function destroy(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $inUse = DB::table('contracts')
            ->where('service_plan_id', $id)
            ->whereIn('status', ['active', 'pending'])
            ->exists();

        if ($inUse) {
            return back()->with('error', 'Impossibile eliminare: il piano è in uso su contratti attivi.');
        }

        DB::table('service_plans')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['deleted_at' => now()]);

        return redirect()->route('service-plans.index')
            ->with('success', 'Piano eliminato.');
    }

    public function toggleActive(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $plan = DB::table('service_plans')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        DB::table('service_plans')->where('id', $id)->update([
            'is_active'  => !$plan->is_active,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Stato piano aggiornato.');
    }
}
