<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractWebController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('contracts')
            ->join('customers', 'contracts.customer_id', '=', 'customers.id')
            ->join('service_plans', 'contracts.service_plan_id', '=', 'service_plans.id')
            ->where('contracts.tenant_id', $tenantId)
            ->select(
                'contracts.*',
                DB::raw("COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS customer_full_name"),
                'customers.codice_fiscale',
                'service_plans.name AS plan_name',
                'service_plans.monthly_fee',
            );

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(customers.first_name || ' ' || customers.last_name) LIKE ?", ['%'.strtolower($search).'%'])
                  ->orWhereRaw('LOWER(customers.codice_fiscale) LIKE ?', ['%'.strtolower($search).'%'])
                  ->orWhereRaw('CAST(contracts.id AS TEXT) = ?', [$search]);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('contracts.status', $status);
        }

        if ($carrier = $request->input('carrier')) {
            $query->where('contracts.carrier', $carrier);
        }

        $contracts = $query->orderByDesc('contracts.created_at')->paginate(25)->withQueryString();

        return view('contracts.index', compact('contracts'));
    }

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $contract = DB::table('contracts')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return view('contracts.show', compact('contract'));
    }

    public function create()
    {
        return view('contracts.create');
    }

    public function edit(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $contract = DB::table('contracts')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        return view('contracts.edit', compact('contract'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'            => 'required|integer',
            'service_plan_id'        => 'required|integer',
            'carrier'                => 'nullable|in:openfiber,fibercop,fwa',
            'installation_address'   => 'required|string|max:500',
        ]);

        $service  = app(\Modules\Contracts\Services\ContractService::class);
        $contract = $service->create(array_merge($validated, [
            'tenant_id'  => auth()->user()->tenant_id,
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('contracts.show', $contract->id)
            ->with('success', 'Contratto creato.');
    }

    public function update(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'installation_address' => 'required|string|max:500',
        ]);

        DB::table('contracts')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(array_merge($validated, ['updated_at' => now()]));

        return redirect()->route('contracts.show', $id)
            ->with('success', 'Contratto aggiornato.');
    }

    public function suspend(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('contracts')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['status' => 'suspended', 'updated_at' => now()]);

        return back()->with('success', 'Contratto sospeso.');
    }

    public function reactivate(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('contracts')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['status' => 'active', 'updated_at' => now()]);

        return back()->with('success', 'Contratto riattivato.');
    }
}
