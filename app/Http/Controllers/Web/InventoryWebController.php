<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryWebController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $stats = [
            'total_items'     => DB::table('inventory_items')->where('tenant_id', $tenantId)->count(),
            'low_stock'       => DB::table('inventory_items')->where('tenant_id', $tenantId)->whereRaw('quantity > 0 AND quantity <= min_quantity')->count(),
            'out_of_stock'    => DB::table('inventory_items')->where('tenant_id', $tenantId)->where('quantity', '<=', 0)->count(),
            'assigned_assets' => DB::table('hardware_assets')->where('tenant_id', $tenantId)->whereNotNull('contract_id')->count(),
        ];

        $items = DB::table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate(25);

        return view('inventory.index', compact('stats', 'items'));
    }

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $item = DB::table('inventory_items')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        return view('inventory.show', compact('item'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku'          => 'required|string|max:100',
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'quantity'     => 'required|integer|min:0',
            'min_quantity' => 'required|integer|min:0',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $id = DB::table('inventory_items')->insertGetId(array_merge($validated, [
            'tenant_id'  => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('inventory.show', $id)
            ->with('success', 'Articolo aggiunto all\'inventario.');
    }

    public function edit(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $item = DB::table('inventory_items')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        return view('inventory.edit', compact('item'));
    }

    public function update(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('inventory_items')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $validated = $request->validate([
            'sku'          => 'required|string|max:100',
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'quantity'     => 'required|integer|min:0',
            'min_quantity' => 'required|integer|min:0',
        ]);

        DB::table('inventory_items')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(array_merge($validated, ['updated_at' => now()]));

        return redirect()->route('inventory.show', $id)
            ->with('success', 'Articolo aggiornato.');
    }

    public function destroy(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $inUse = DB::table('hardware_assets')
            ->where('tenant_id', $tenantId)
            ->where('inventory_item_id', $id)
            ->exists();

        if ($inUse) {
            return back()->with('error', 'Impossibile eliminare: articolo in uso su asset hardware.');
        }

        DB::table('inventory_items')->where('id', $id)->where('tenant_id', $tenantId)->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Articolo eliminato dall\'inventario.');
    }
}
