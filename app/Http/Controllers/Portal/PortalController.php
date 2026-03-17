<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Billing\OnlinePayments\Services\PaymentGatewayFactory;
use Modules\Billing\OnlinePayments\Models\OnlinePaymentMethod;

class PortalController extends Controller
{
    private function customer()
    {
        return auth('portal')->user();
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $customerId = $this->customer()->id;

        $contracts = DB::table('contracts as c')
            ->join('service_plans as sp', 'c.service_plan_id', '=', 'sp.id')
            ->where('c.customer_id', $customerId)
            ->whereIn('c.status', ['active', 'pending_signature', 'suspended'])
            ->selectRaw('c.*, sp.name AS plan_name, sp.bandwidth_dl, sp.bandwidth_ul, sp.technology')
            ->orderByDesc('c.activation_date')
            ->get();

        $pendingInvoices = DB::table('invoices')
            ->where('customer_id', $customerId)
            ->whereIn('status', ['issued', 'overdue'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $openTickets = DB::table('trouble_tickets')
            ->where('customer_id', $customerId)
            ->whereIn('status', ['open', 'in_progress', 'pending'])
            ->orderByDesc('opened_at')
            ->limit(5)
            ->get();

        $totalPaid = DB::table('invoices')
            ->where('customer_id', $customerId)
            ->where('status', 'paid')
            ->sum('total');

        $totalOverdue = DB::table('invoices')
            ->where('customer_id', $customerId)
            ->where('status', 'overdue')
            ->sum('total');

        return view('portal.dashboard', compact('contracts', 'pendingInvoices', 'openTickets', 'totalPaid', 'totalOverdue'));
    }

    // ── Fatture ───────────────────────────────────────────────────────────────

    public function invoices(Request $request)
    {
        $customerId = $this->customer()->id;

        $query = DB::table('invoices')
            ->where('customer_id', $customerId)
            ->whereNotIn('status', ['draft', 'cancelled']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($year = $request->input('year')) {
            $query->whereYear('issue_date', $year);
        }

        $invoices = $query->orderByDesc('issue_date')->paginate(15)->withQueryString();

        $years = DB::table('invoices')
            ->where('customer_id', $customerId)
            ->selectRaw('EXTRACT(YEAR FROM issue_date)::int AS y')
            ->groupByRaw('EXTRACT(YEAR FROM issue_date)::int')
            ->orderByDesc('y')
            ->pluck('y');

        return view('portal.invoices.index', compact('invoices', 'years'));
    }

    public function invoiceShow(int $id)
    {
        $invoice = DB::table('invoices')
            ->where('id', $id)
            ->where('customer_id', $this->customer()->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->firstOrFail();

        $items = DB::table('invoice_items')->where('invoice_id', $id)->get();

        $payments = DB::table('payments')
            ->where('invoice_id', $id)
            ->where('status', 'completed')
            ->get();

        return view('portal.invoices.show', compact('invoice', 'items', 'payments'));
    }

    public function invoicePdf(int $id)
    {
        $invoice = DB::table('invoices')
            ->where('id', $id)
            ->where('customer_id', $this->customer()->id)
            ->firstOrFail();

        $service = app(\Modules\Billing\Services\InvoiceService::class);
        $pdf     = $service->generatePdf($invoice->id);

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $invoice->number . '.pdf"');
    }

    // ── Ticket ────────────────────────────────────────────────────────────────

    public function tickets(Request $request)
    {
        $customerId = $this->customer()->id;

        $query = DB::table('trouble_tickets')
            ->where('customer_id', $customerId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $tickets = $query->orderByDesc('opened_at')->paginate(15)->withQueryString();

        return view('portal.tickets.index', compact('tickets'));
    }

    public function ticketCreate()
    {
        $customer   = $this->customer();
        $contracts  = DB::table('contracts as c')
            ->join('service_plans as sp', 'c.service_plan_id', '=', 'sp.id')
            ->where('c.customer_id', $customer->id)
            ->where('c.status', 'active')
            ->selectRaw('c.id, sp.name AS plan_name, c.carrier')
            ->get();

        return view('portal.tickets.create', compact('contracts'));
    }

    public function ticketStore(Request $request)
    {
        $customer = $this->customer();

        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'required|string|max:5000',
            'type'        => 'required|in:assurance,billing,provisioning,other',
            'contract_id' => 'nullable|integer',
            'priority'    => 'required|in:low,medium,high',
        ]);

        // Verifica che il contratto appartenga al cliente
        if ($data['contract_id']) {
            $owns = DB::table('contracts')
                ->where('id', $data['contract_id'])
                ->where('customer_id', $customer->id)
                ->exists();
            if (!$owns) {
                abort(403);
            }
        }

        $ticketNumber = 'TK-' . now()->format('Ymd') . '-' . str_pad(
            DB::table('trouble_tickets')->where('tenant_id', $customer->tenant_id)->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        DB::table('trouble_tickets')->insert([
            'tenant_id'     => $customer->tenant_id,
            'customer_id'   => $customer->id,
            'contract_id'   => $data['contract_id'] ?? null,
            'ticket_number' => $ticketNumber,
            'title'         => $data['title'],
            'description'   => $data['description'],
            'type'          => $data['type'],
            'priority'      => $data['priority'],
            'status'        => 'open',
            'source'        => 'portal',
            'opened_at'     => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('portal.tickets')
            ->with('success', 'Richiesta inviata. Il numero ticket è ' . $ticketNumber);
    }

    public function ticketShow(string $ticketNumber)
    {
        $ticket = DB::table('trouble_tickets')
            ->where('ticket_number', $ticketNumber)
            ->where('customer_id', $this->customer()->id)
            ->firstOrFail();

        $notes = DB::table('ticket_notes')
            ->where('ticket_id', $ticket->id)
            ->where('is_internal', false)
            ->orderBy('created_at')
            ->get();

        return view('portal.tickets.show', compact('ticket', 'notes'));
    }

    // ── Pagamenti ─────────────────────────────────────────────────────────────

    public function payInvoice(int $id)
    {
        $customer = $this->customer();

        $invoice = DB::table('invoices')
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->firstOrFail();

        $methods = DB::table('online_payment_methods')
            ->where('customer_id', $customer->id)
            ->where('active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return view('portal.payments.pay', compact('invoice', 'methods'));
    }

    public function initiatePayment(int $id)
    {
        $customer = $this->customer();

        // Load invoice — Eloquent needed for gateway compatibility
        $invoice = \Modules\Billing\Models\Invoice::where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->firstOrFail();

        $gateway = app(PaymentGatewayFactory::class)->make();
        $url     = $gateway->createPaymentLink($customer, $invoice);

        return redirect($url);
    }

    public function chargeMethod(int $invoiceId, int $methodId)
    {
        $customer = $this->customer();

        $invoice = \Modules\Billing\Models\Invoice::where('id', $invoiceId)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->firstOrFail();

        $method = OnlinePaymentMethod::where('id', $methodId)
            ->where('customer_id', $customer->id)
            ->where('active', true)
            ->firstOrFail();

        $gateway = app(PaymentGatewayFactory::class)->make($method->gateway);

        try {
            $gateway->chargeRecurring($method, $invoice);
        } catch (\Throwable $e) {
            return back()->with('error', 'Pagamento non riuscito: ' . $e->getMessage());
        }

        return redirect()->route('portal.payments.success', ['invoice_id' => $invoiceId])
            ->with('success', 'Pagamento inviato con successo.');
    }

    public function paymentSuccess(Request $request)
    {
        $invoice = null;
        if ($id = $request->query('invoice_id')) {
            $invoice = DB::table('invoices')
                ->where('id', $id)
                ->where('customer_id', $this->customer()->id)
                ->first();
        }

        return view('portal.payments.success', compact('invoice'));
    }

    public function paymentCancelled()
    {
        return view('portal.payments.cancelled');
    }

    public function paymentMethods()
    {
        $methods = DB::table('online_payment_methods')
            ->where('customer_id', $this->customer()->id)
            ->where('active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return view('portal.payments.methods', compact('methods'));
    }

    public function deletePaymentMethod(int $id)
    {
        DB::table('online_payment_methods')
            ->where('id', $id)
            ->where('customer_id', $this->customer()->id)
            ->update(['active' => false, 'updated_at' => now()]);

        return back()->with('success', 'Metodo di pagamento rimosso.');
    }
}
