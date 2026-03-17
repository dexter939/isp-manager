<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceWebController extends Controller
{
    // ── On-Call ───────────────────────────────────────────────────────────────

    public function onCallIndex()
    {
        $tenantId = auth()->user()->tenant_id;
        $now      = now();

        $currentSlot = DB::table('oncall_schedule_slots as s')
            ->join('oncall_schedules as sc', 's.oncall_schedule_id', '=', 'sc.id')
            ->join('users', 's.user_id', '=', 'users.id')
            ->where('sc.tenant_id', $tenantId)
            ->where('s.starts_at', '<=', $now)
            ->where('s.ends_at', '>', $now)
            ->orderBy('s.level')
            ->selectRaw('s.*, users.name AS technician_name, s.level AS oncall_level')
            ->first();

        $currentOnCall = $currentSlot ? ['name' => $currentSlot->technician_name, 'level' => $currentSlot->oncall_level] : null;

        $nextShift = DB::table('oncall_schedule_slots')
            ->join('oncall_schedules', 'oncall_schedule_slots.oncall_schedule_id', '=', 'oncall_schedules.id')
            ->where('oncall_schedules.tenant_id', $tenantId)
            ->where('oncall_schedule_slots.starts_at', '>', $now)
            ->orderBy('oncall_schedule_slots.starts_at')
            ->value('oncall_schedule_slots.starts_at');

        // Build weekly calendar: level → [Mon..Sun]
        $weekStart = $now->startOfWeek()->copy();
        $weekSchedule = [];
        for ($level = 1; $level <= 2; $level++) {
            $weekSchedule["L{$level}"] = [];
            for ($i = 0; $i < 7; $i++) {
                $day = $weekStart->copy()->addDays($i);
                $slot = DB::table('oncall_schedule_slots as s')
                    ->join('oncall_schedules', 's.oncall_schedule_id', '=', 'oncall_schedules.id')
                    ->join('users', 's.user_id', '=', 'users.id')
                    ->where('oncall_schedules.tenant_id', $tenantId)
                    ->where('s.level', $level)
                    ->whereDate('s.starts_at', $day->toDateString())
                    ->value('users.name');
                $weekSchedule["L{$level}"][] = $slot;
            }
        }

        $recentDispatches = DB::table('oncall_alert_dispatches as d')
            ->join('users', 'd.user_id', '=', 'users.id')
            ->join('oncall_schedule_slots as s', 'd.oncall_schedule_slot_id', '=', 's.id')
            ->join('oncall_schedules', 's.oncall_schedule_id', '=', 'oncall_schedules.id')
            ->where('oncall_schedules.tenant_id', $tenantId)
            ->selectRaw('d.*, users.name AS technician_name')
            ->orderByDesc('d.created_at')
            ->limit(20)
            ->get();

        return view('maintenance.oncall', compact('currentOnCall', 'nextShift', 'weekSchedule', 'recentDispatches'));
    }

    // ── Dispatcher ────────────────────────────────────────────────────────────

    public function dispatcherAssignmentStore(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'intervention_id'            => ['required', 'uuid'],
            'user_id'                    => ['required', 'uuid'],
            'scheduled_start'            => ['required', 'date'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'notes'                      => ['nullable', 'string', 'max:500'],
        ]);

        $start = \Carbon\Carbon::parse($data['scheduled_start']);
        $end   = $start->copy()->addMinutes($data['estimated_duration_minutes']);

        // Conflict check
        $conflict = DB::table('dispatch_assignments')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $data['user_id'])
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_start', '<', $end)
            ->where('scheduled_end',   '>', $start)
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Conflitto di orario: il tecnico ha già un intervento in questa fascia.');
        }

        DB::table('dispatch_assignments')->insert([
            'id'                          => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'                   => $tenantId,
            'intervention_id'             => $data['intervention_id'],
            'user_id'                     => $data['user_id'],
            'scheduled_start'             => $start,
            'scheduled_end'               => $end,
            'estimated_duration_minutes'  => $data['estimated_duration_minutes'],
            'travel_time_minutes'         => 0,
            'status'                      => 'scheduled',
            'assigned_by'                 => auth()->id(),
            'notes'                       => $data['notes'] ?? null,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        return back()->with('success', 'Intervento assegnato.');
    }

    public function dispatcherAssignmentDestroy(string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        DB::table('dispatch_assignments')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
        return back()->with('success', 'Assegnazione annullata.');
    }

    public function dispatcherIndex(Request $request)
    {
        $tenantId    = auth()->user()->tenant_id;
        $date        = $request->input('date', today()->toDateString());
        $technicianId = $request->input('technician_id');

        $technicians = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereJsonContains('roles', 'technician')
            ->orWhere(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->whereNotNull('daily_capacity_hours');
            })
            ->get();

        // Build timeline: techName → [hour => assignment]
        $assignmentsQuery = DB::table('dispatch_assignments as da')
            ->join('users', 'da.user_id', '=', 'users.id')
            ->where('da.tenant_id', $tenantId)
            ->whereDate('da.scheduled_start', $date)
            ->selectRaw('da.*, users.name AS tech_name');

        if ($technicianId) {
            $assignmentsQuery->where('da.user_id', $technicianId);
        }

        $assignments = $assignmentsQuery->get();
        $timeline    = [];
        foreach ($assignments as $a) {
            $hour = (int) date('H', strtotime($a->scheduled_start));
            $timeline[$a->tech_name][$hour] = ['title' => $a->title ?? 'Intervento'];
        }

        $conflicts = DB::table('dispatch_assignments as a')
            ->join('dispatch_assignments as b', function ($join) {
                $join->on('a.user_id', '=', 'b.user_id')
                     ->whereRaw('a.id < b.id')
                     ->whereRaw('a.scheduled_start < b.scheduled_end')
                     ->whereRaw('b.scheduled_start < a.scheduled_end');
            })
            ->where('a.tenant_id', $tenantId)
            ->selectRaw('a.id AS a_id, b.id AS b_id, a.user_id')
            ->limit(10)
            ->get();

        $unassigned = DB::table('trouble_tickets')
            ->where('tenant_id', $tenantId)
            ->whereNull('assigned_to')
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->get();

        return view('maintenance.dispatcher', compact('technicians', 'timeline', 'conflicts', 'unassigned'));
    }

    // ── Inventory RMA ─────────────────────────────────────────────────────────

    public function inventoryRmaIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $itemQuery = DB::table('inventory_items as ii')
            ->leftJoin('inventory_models as im', 'ii.inventory_model_id', '=', 'im.id')
            ->where('ii.tenant_id', $tenantId)
            ->selectRaw('ii.*, im.name AS model_name');

        if ($model = $request->input('model')) {
            $itemQuery->whereRaw('LOWER(im.name) LIKE ?', ['%'.strtolower($model).'%']);
        }
        if ($status = $request->input('item_status')) {
            $itemQuery->where('ii.status', $status);
        }

        $inventoryItems = $itemQuery->orderBy('ii.created_at', 'desc')->paginate(25)->withQueryString();

        $activeRma = DB::table('rma_requests as r')
            ->leftJoin('inventory_items as ii', 'r.inventory_item_id', '=', 'ii.id')
            ->leftJoin('inventory_models as im', 'ii.inventory_model_id', '=', 'im.id')
            ->where('r.tenant_id', $tenantId)
            ->whereNotIn('r.status', ['resolved', 'rejected', 'closed'])
            ->selectRaw('r.*, ii.serial_number, im.name AS model_name')
            ->orderByDesc('r.created_at')
            ->get();

        $historyRma = DB::table('rma_requests as r')
            ->leftJoin('inventory_items as ii', 'r.inventory_item_id', '=', 'ii.id')
            ->leftJoin('inventory_models as im', 'ii.inventory_model_id', '=', 'im.id')
            ->where('r.tenant_id', $tenantId)
            ->whereIn('r.status', ['resolved', 'rejected', 'closed'])
            ->selectRaw('r.*, ii.serial_number, im.name AS model_name')
            ->orderByDesc('r.resolved_at')
            ->limit(50)
            ->get();

        $totalItems  = DB::table('inventory_items')->where('tenant_id', $tenantId)->count();
        $openRma     = DB::table('rma_requests')->where('tenant_id', $tenantId)->whereNotIn('status', ['resolved', 'rejected', 'closed'])->count();
        $defectRate  = $totalItems > 0
            ? round(DB::table('rma_requests')->where('tenant_id', $tenantId)->count() / $totalItems * 100, 1)
            : 0.0;

        $stats = compact('totalItems', 'openRma', 'defectRate');
        $stats = ['total_items' => $totalItems, 'open_rma' => $openRma, 'defect_rate' => $defectRate];

        return view('maintenance.inventory-rma', compact('inventoryItems', 'activeRma', 'historyRma', 'stats'));
    }

    // ── Purchase Orders ───────────────────────────────────────────────────────

    public function purchaseOrderStore(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'supplier_id'     => ['required', 'uuid'],
            'expected_delivery' => ['nullable', 'date', 'after:today'],
            'notes'           => ['nullable', 'string'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.inventory_model_id' => ['required', 'uuid'],
            'items.*.quantity_ordered'   => ['required', 'integer', 'min:1'],
            'items.*.unit_price_amount'  => ['required', 'integer', 'min:0'],
        ]);

        $totalAmount = collect($data['items'])->sum(fn ($i) => $i['quantity_ordered'] * $i['unit_price_amount']);

        DB::transaction(function () use ($data, $totalAmount, $tenantId) {
            $poId = (string) \Illuminate\Support\Str::uuid();

            DB::table('purchase_orders')->insert([
                'id'               => $poId,
                'tenant_id'        => $tenantId,
                'supplier_id'      => $data['supplier_id'],
                'status'           => 'draft',
                'total_amount'     => $totalAmount,
                'total_currency'   => 'EUR',
                'expected_delivery' => $data['expected_delivery'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'created_by'       => 'manual',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            foreach ($data['items'] as $item) {
                DB::table('purchase_order_items')->insert([
                    'id'                  => (string) \Illuminate\Support\Str::uuid(),
                    'purchase_order_id'   => $poId,
                    'inventory_model_id'  => $item['inventory_model_id'],
                    'quantity_ordered'    => $item['quantity_ordered'],
                    'quantity_received'   => 0,
                    'unit_price_amount'   => $item['unit_price_amount'],
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        });

        return back()->with('success', 'Ordine di acquisto creato in stato Bozza.');
    }

    public function purchaseOrderApprove(string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $po = DB::table('purchase_orders')->where('id', $id)->where('tenant_id', $tenantId)->first();
        if (!$po) abort(404);

        DB::table('purchase_orders')->where('id', $id)->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'Ordine approvato. Ora puoi inviarlo al fornitore.');
    }

    public function purchaseOrderReceive(Request $request, string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $po = DB::table('purchase_orders')->where('id', $id)->where('tenant_id', $tenantId)->first();
        if (!$po) abort(404);

        $data = $request->validate([
            'items'                      => ['required', 'array'],
            'items.*.item_id'            => ['required', 'uuid'],
            'items.*.quantity_received'  => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $id, $po) {
            foreach ($data['items'] as $item) {
                DB::table('purchase_order_items')
                    ->where('id', $item['item_id'])
                    ->where('purchase_order_id', $id)
                    ->update(['quantity_received' => $item['quantity_received'], 'updated_at' => now()]);
            }

            // Check if fully or partially received
            $items    = DB::table('purchase_order_items')->where('purchase_order_id', $id)->get();
            $allDone  = $items->every(fn ($i) => $i->quantity_received >= $i->quantity_ordered);
            $anyDone  = $items->some(fn ($i) => $i->quantity_received > 0);

            DB::table('purchase_orders')->where('id', $id)->update([
                'status'     => $allDone ? 'received' : ($anyDone ? 'partial' : $po->status),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Ricezione registrata.');
    }

    public function purchaseOrdersIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $poQuery = DB::table('purchase_orders as po')
            ->leftJoin('suppliers', 'po.supplier_id', '=', 'suppliers.id')
            ->where('po.tenant_id', $tenantId)
            ->selectRaw('po.*, suppliers.name AS supplier_name');

        if ($status = $request->input('status')) {
            $poQuery->where('po.status', $status);
        }

        $purchaseOrders = $poQuery->orderByDesc('po.created_at')->paginate(25)->withQueryString();

        $reorderRules = DB::table('reorder_rules as rr')
            ->leftJoin('inventory_models as im', 'rr.inventory_model_id', '=', 'im.id')
            ->leftJoin('suppliers', 'rr.supplier_id', '=', 'suppliers.id')
            ->where('rr.tenant_id', $tenantId)
            ->selectRaw('rr.*, im.name AS model_name, suppliers.name AS supplier_name')
            ->orderBy('im.name')
            ->get();

        $stats = [
            'pending'           => DB::table('purchase_orders')->where('tenant_id', $tenantId)->where('status', 'sent')->count(),
            'this_month'        => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereMonth('created_at', now()->month)->count(),
            'total_value_cents' => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereMonth('created_at', now()->month)->sum('total_amount_cents'),
        ];

        return view('maintenance.purchase-orders', compact('purchaseOrders', 'reorderRules', 'stats'));
    }

    // ── Route Optimizer ───────────────────────────────────────────────────────

    public function routeOptimizerGenerate(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'technician_id' => ['required', 'uuid'],
            'plan_date'     => ['required', 'date'],
            'start_address' => ['nullable', 'string', 'max:255'],
            'start_lat'     => ['nullable', 'numeric'],
            'start_lon'     => ['nullable', 'numeric'],
        ]);

        // Check if plan already exists for this technician + date
        $existing = DB::table('route_plans')
            ->where('tenant_id', $tenantId)
            ->where('technician_id', $data['technician_id'])
            ->where('plan_date', $data['plan_date'])
            ->whereIn('status', ['draft', 'active'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Esiste già un piano attivo per questo tecnico in questa data.');
        }

        $planId = (string) \Illuminate\Support\Str::uuid();

        DB::table('route_plans')->insert([
            'id'             => $planId,
            'tenant_id'      => $tenantId,
            'technician_id'  => $data['technician_id'],
            'plan_date'      => $data['plan_date'],
            'start_address'  => $data['start_address'] ?? null,
            'start_lat'      => $data['start_lat'] ?? 0,
            'start_lon'      => $data['start_lon'] ?? 0,
            'optimized_order' => json_encode([]),
            'status'         => 'draft',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Dispatch optimization job (non-blocking)
        try {
            \Illuminate\Support\Facades\Artisan::queue('routes:optimize', [
                '--plan' => $planId,
            ]);
        } catch (\Throwable) {
            // Job dispatch is best-effort; plan is created regardless
        }

        return back()->with('success', 'Piano creato. L\'ottimizzazione del percorso verrà calcolata in background.');
    }

    public function routeOptimizerIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $routePlans = DB::table('route_plans')
            ->join('users', 'route_plans.technician_id', '=', 'users.id')
            ->where('route_plans.tenant_id', $tenantId)
            ->selectRaw('route_plans.*, users.name AS tech_name')
            ->orderByDesc('route_plans.created_at')
            ->paginate(25)->withQueryString();

        $technicians = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('daily_capacity_hours')
            ->orderBy('name')
            ->get();

        return view('maintenance.route-optimizer', compact('routePlans', 'technicians'));
    }
}
