<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerWebController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->selectRaw("*, COALESCE(company_name, first_name || ' ' || last_name) AS full_name")
            ->withCount('contracts');  // not possible with raw DB — use Eloquent if model exists

        // Fall back to plain query with sub-select count
        $query = DB::table('customers')
            ->where('customers.tenant_id', $tenantId)
            ->selectRaw("customers.*, COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS full_name,
                (SELECT COUNT(*) FROM contracts WHERE contracts.customer_id = customers.id) AS contracts_count");

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", ['%'.strtolower($search).'%'])
                  ->orWhereRaw('LOWER(email) LIKE ?', ['%'.strtolower($search).'%'])
                  ->orWhereRaw('LOWER(codice_fiscale) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $customers = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $customer = DB::table('customers')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        return view('customers.show', compact('customer'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $service  = app(\Modules\Contracts\Services\CustomerService::class);
        $customer = $service->create($request->all(), auth()->user()->tenant_id);

        return redirect()->route('customers.show', $customer->id)
            ->with('success', 'Cliente creato con successo.');
    }

    public function edit(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $customer = DB::table('customers')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $customer = \Modules\Contracts\Models\Customer::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $service = app(\Modules\Contracts\Services\CustomerService::class);
        $service->update($customer, $request->all());

        return redirect()->route('customers.show', $id)
            ->with('success', 'Cliente aggiornato con successo.');
    }

    public function destroy(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $customer = \Modules\Contracts\Models\Customer::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Blocca se ha contratti attivi
        $hasActive = DB::table('contracts')
            ->where('customer_id', $id)
            ->whereIn('status', ['active', 'pending'])
            ->exists();

        if ($hasActive) {
            return back()->with('error', 'Impossibile eliminare: il cliente ha contratti attivi.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Cliente eliminato.');
    }
}
