<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Enums\OrderType;
use Modules\Provisioning\Jobs\SendActivationOrderJob;
use Modules\Provisioning\Jobs\SendDeactivationOrderJob;
use Modules\Provisioning\Models\CarrierOrder;
use Modules\Provisioning\Services\CarrierGateway;
use Modules\Provisioning\Services\OrderStateMachine;
use Modules\Provisioning\Services\VlanManager;

class ProvisioningWebController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('carrier_orders as co')
            ->join('contracts as c',  'c.id',  '=', 'co.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->where('co.tenant_id', $tenantId)
            ->whereNull('co.deleted_at')
            ->select(
                'co.id',
                'co.codice_ordine_olo',
                'co.codice_ordine_of',
                'co.carrier',
                'co.order_type',
                'co.state',
                'co.scheduled_date',
                'co.sent_at',
                'co.completed_at',
                'co.retry_count',
                'co.last_error',
                'co.created_at',
                'c.id as contract_id',
                'c.contract_number',
                'cu.id as customer_id',
                DB::raw("cu.first_name || ' ' || cu.last_name as customer_name"),
                'cu.company_name'
            );

        if ($request->filled('state')) {
            $query->where('co.state', $request->state);
        }
        if ($request->filled('carrier')) {
            $query->where('co.carrier', $request->carrier);
        }
        if ($request->filled('order_type')) {
            $query->where('co.order_type', $request->order_type);
        }
        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('co.codice_ordine_olo', 'ilike', $q)
                    ->orWhere('co.codice_ordine_of', 'ilike', $q)
                    ->orWhere('cu.first_name', 'ilike', $q)
                    ->orWhere('cu.last_name', 'ilike', $q)
                    ->orWhere('cu.company_name', 'ilike', $q);
            });
        }

        $orders = $query->orderByDesc('co.created_at')->paginate(20)->withQueryString();

        // KPI cards
        $kpis = DB::table('carrier_orders')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->selectRaw("
                count(*) filter (where state not in ('completed','cancelled','retry_failed')) as active,
                count(*) filter (where state = 'draft')                                      as draft,
                count(*) filter (where state in ('ko','retry_failed'))                       as ko_count,
                count(*) filter (where state = 'completed' and completed_at::date = current_date) as completed_today
            ")
            ->first();

        $carriers   = ['openfiber', 'fibercop', 'fastweb'];
        $orderTypes = OrderType::cases();
        $states     = OrderState::cases();

        return view('provisioning.index', compact('orders', 'kpis', 'carriers', 'orderTypes', 'states'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $order = DB::table('carrier_orders as co')
            ->join('contracts as c',  'c.id',  '=', 'co.contract_id')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->leftJoin('users as u',  'u.id',  '=', 'co.sent_by')
            ->where('co.id', $id)
            ->where('co.tenant_id', $tenantId)
            ->whereNull('co.deleted_at')
            ->selectRaw("
                co.*,
                c.contract_number,
                c.carrier as contract_carrier,
                cu.id              as customer_id,
                cu.first_name || ' ' || cu.last_name as customer_name,
                cu.company_name,
                u.name             as sent_by_name
            ")
            ->first();

        abort_if(! $order, 404);

        $events = DB::table('carrier_events_log')
            ->where('carrier_order_id', $id)
            ->orderByDesc('logged_at')
            ->limit(50)
            ->get();

        $stateObj = OrderState::from($order->state);

        // Which transitions are currently available
        $canSend      = $order->state === 'draft';
        $canCancel    = in_array($order->state, ['draft', 'sent', 'accepted', 'scheduled', 'in_progress', 'ko', 'suspended']);
        $canReschedule= in_array($order->state, ['accepted', 'scheduled']);
        $canUnsuspend = $order->state === 'suspended';

        return view('provisioning.show', compact('order', 'events', 'stateObj', 'canSend', 'canCancel', 'canReschedule', 'canUnsuspend'));
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function send(int $id)
    {
        $order = $this->findOrder($id);

        if ($order->state !== 'draft') {
            return back()->with('error', 'L\'ordine non è in stato bozza.');
        }

        $job = $order->order_type === 'deactivation'
            ? new SendDeactivationOrderJob($order)
            : new SendActivationOrderJob($order);

        dispatch($job)->onQueue('carrier-orders');

        return back()->with('success', 'Ordine messo in coda per l\'invio al carrier.');
    }

    public function cancel(Request $request, int $id)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $order      = $this->findOrder($id);
        $machine    = app(OrderStateMachine::class);

        try {
            $machine->cancel($order, $request->input('reason', ''));
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Ordine annullato.');
    }

    public function reschedule(Request $request, int $id)
    {
        $request->validate(['scheduled_date' => 'required|date|after:today']);

        $order   = $this->findOrder($id);
        $gateway = app(CarrierGateway::class);

        try {
            $gateway->sendReschedule($order, \Carbon\Carbon::parse($request->scheduled_date));
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore rimodulazione: ' . $e->getMessage());
        }

        return back()->with('success', 'Rimodulazione inviata al carrier.');
    }

    public function unsuspend(int $id)
    {
        $order   = $this->findOrder($id);
        $gateway = app(CarrierGateway::class);

        try {
            $gateway->sendUnsuspend($order);
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore desospensione: ' . $e->getMessage());
        }

        return back()->with('success', 'Desospensione inviata al carrier.');
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Contracts attivi del tenant che non hanno già un ordine draft/in-progress
        $contracts = DB::table('contracts as c')
            ->join('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->where('c.tenant_id', $tenantId)
            ->where('c.status', 'active')
            ->selectRaw("c.id, c.contract_number, c.carrier,
                COALESCE(cu.company_name, cu.first_name || ' ' || cu.last_name) AS customer_name")
            ->orderBy('c.contract_number')
            ->get();

        $orderTypes = OrderType::cases();
        $carriers   = ['openfiber', 'fibercop', 'fastweb'];

        // Pre-select contract se passato come querystring
        $selectedContract = null;
        if ($request->filled('contract_id')) {
            $selectedContract = $contracts->firstWhere('id', (int) $request->input('contract_id'));
        }

        return view('provisioning.create', compact('contracts', 'orderTypes', 'carriers', 'selectedContract'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => ['required', 'integer'],
            'order_type'  => ['required', 'in:activation,change,deactivation,migration'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId  = auth()->user()->tenant_id;

        // Verifica che il contratto appartenga al tenant
        $contract = DB::table('contracts')
            ->where('id', $request->contract_id)
            ->where('tenant_id', $tenantId)
            ->first();

        abort_if(!$contract, 404, 'Contratto non trovato.');

        // Genera codice OLO univoco
        $year      = now()->format('Y');
        $seq       = DB::table('carrier_orders')->count() + 1;
        $oloCode   = 'ISP-' . $year . '-' . $tenantId . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);

        $orderId = DB::table('carrier_orders')->insertGetId([
            'tenant_id'         => $tenantId,
            'contract_id'       => $contract->id,
            'carrier'           => $contract->carrier,
            'order_type'        => $request->order_type,
            'codice_ordine_olo' => $oloCode,
            'state'             => 'draft',
            'notes'             => $request->notes,
            'retry_count'       => 0,
            'sent_by'           => auth()->id(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return redirect()
            ->route('provisioning.show', $orderId)
            ->with('success', "Ordine {$oloCode} creato in bozza. Verificare i dettagli e inviare al carrier.");
    }

    // ── VLAN Pool ─────────────────────────────────────────────────────────────

    public function vlanPool(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Summary per carrier/type
        $summary = DB::table('vlan_pool')
            ->where('tenant_id', $tenantId)
            ->selectRaw("carrier, vlan_type,
                count(*) as total,
                count(*) filter (where status = 'free')     as free_count,
                count(*) filter (where status = 'assigned') as assigned_count,
                count(*) filter (where status = 'reserved') as reserved_count")
            ->groupBy('carrier', 'vlan_type')
            ->orderBy('carrier')
            ->orderBy('vlan_type')
            ->get();

        // Detailed table
        $query = DB::table('vlan_pool as vp')
            ->leftJoin('contracts as c', 'c.id', '=', 'vp.contract_id')
            ->leftJoin('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->where('vp.tenant_id', $tenantId)
            ->select(
                'vp.id', 'vp.carrier', 'vp.vlan_type', 'vp.vlan_id',
                'vp.status', 'vp.assigned_at', 'vp.notes',
                'c.contract_number',
                DB::raw("cu.first_name || ' ' || cu.last_name as customer_name"),
                'cu.company_name'
            );

        if ($request->filled('carrier')) {
            $query->where('vp.carrier', $request->carrier);
        }
        if ($request->filled('vlan_type')) {
            $query->where('vp.vlan_type', $request->vlan_type);
        }
        if ($request->filled('status')) {
            $query->where('vp.status', $request->status);
        }

        $vlans    = $query->orderBy('vp.carrier')->orderBy('vp.vlan_id')->paginate(50)->withQueryString();
        $carriers = ['openfiber', 'fibercop', 'fastweb'];

        return view('provisioning.vlan-pool', compact('summary', 'vlans', 'carriers'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findOrder(int $id): CarrierOrder
    {
        $tenantId = auth()->user()->tenant_id;

        return CarrierOrder::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }
}
