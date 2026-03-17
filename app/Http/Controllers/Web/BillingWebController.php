<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Services\SddService;

class BillingWebController extends Controller
{
    // ── Invoices ──────────────────────────────────────────────────────────────

    public function invoiceIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('invoices')
            ->join('contracts', 'invoices.contract_id', '=', 'contracts.id')
            ->join('customers', 'contracts.customer_id', '=', 'customers.id')
            ->where('invoices.tenant_id', $tenantId)
            ->selectRaw("invoices.*, COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS customer_full_name");

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(invoices.number) LIKE ?', ['%'.strtolower($search).'%'])
                  ->orWhereRaw("LOWER(customers.first_name || ' ' || customers.last_name) LIKE ?", ['%'.strtolower($search).'%']);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('invoices.status', $status);
        }

        if ($month = $request->input('month')) {
            $query->whereRaw("TO_CHAR(invoices.issue_date, 'YYYY-MM') = ?", [$month]);
        }

        if ($contractId = $request->input('contract_id')) {
            $query->where('invoices.contract_id', $contractId);
        }

        $invoices = $query->orderByDesc('invoices.issue_date')->paginate(25)->withQueryString();

        return view('billing.invoices.index', compact('invoices'));
    }

    public function invoiceShow(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $invoice = DB::table('invoices')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        return view('billing.invoices.show', compact('invoice'));
    }

    public function invoicePdf(int $id)
    {
        // Delegates to Billing module InvoiceService — generate PDF and stream
        $tenantId = auth()->user()->tenant_id;
        $invoice  = DB::table('invoices')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $service = app(\Modules\Billing\Services\InvoiceService::class);
        $pdf     = $service->generatePdf($invoice->id);

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $invoice->number . '.pdf"');
    }

    // ── Proforma ──────────────────────────────────────────────────────────────

    public function proformaIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('invoices')
            ->join('contracts', 'invoices.contract_id', '=', 'contracts.id')
            ->join('customers', 'contracts.customer_id', '=', 'customers.id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.invoice_type', 'proforma')
            ->selectRaw("invoices.*,
                COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS customer_full_name");

        if ($status = $request->input('status')) {
            if ($status === 'converted') {
                $query->whereNotNull('invoices.converted_at');
            } elseif ($status === 'expired') {
                $query->whereNull('invoices.converted_at')->where('invoices.due_date', '<', now());
            } else {
                $query->whereNull('invoices.converted_at')->where('invoices.due_date', '>=', now());
            }
        }
        if ($from = $request->input('date_from')) {
            $query->where('invoices.issue_date', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->where('invoices.issue_date', '<=', $to);
        }

        $proformas = $query->orderByDesc('invoices.issue_date')->paginate(25)->withQueryString();

        $stats = [
            'pending'         => DB::table('invoices')->where('tenant_id', $tenantId)->where('invoice_type', 'proforma')->whereNull('converted_at')->where('due_date', '>=', now())->count(),
            'expired_today'   => DB::table('invoices')->where('tenant_id', $tenantId)->where('invoice_type', 'proforma')->whereNull('converted_at')->whereDate('due_date', today())->count(),
            'converted_month' => DB::table('invoices')->where('tenant_id', $tenantId)->where('invoice_type', 'proforma')->whereNotNull('converted_at')->whereMonth('converted_at', now()->month)->count(),
        ];

        return view('billing.proforma.index', compact('proformas', 'stats'));
    }

    // ── Bundles ───────────────────────────────────────────────────────────────

    public function bundleStore(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string'],
            'price_amount'   => ['required', 'integer', 'min:0'],
            'billing_period' => ['required', 'in:monthly,bimonthly,quarterly,semiannual,annual'],
        ]);

        DB::table('bundle_plans')->insert([
            'id'             => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'      => $tenantId,
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'price_amount'   => $data['price_amount'],
            'price_currency' => 'EUR',
            'billing_period' => $data['billing_period'],
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return back()->with('success', 'Piano bundle creato.');
    }

    public function bundleUpdate(Request $request, string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string'],
            'price_amount'   => ['required', 'integer', 'min:0'],
            'billing_period' => ['required', 'in:monthly,bimonthly,quarterly,semiannual,annual'],
        ]);

        DB::table('bundle_plans')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(array_merge($data, ['updated_at' => now()]));

        return back()->with('success', 'Piano bundle aggiornato.');
    }

    public function bundleDestroy(string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $hasSubscriptions = DB::table('bundle_subscriptions')
            ->where('bundle_plan_id', $id)
            ->where('status', 'active')
            ->exists();

        if ($hasSubscriptions) {
            return back()->with('error', 'Impossibile eliminare: esistono abbonamenti attivi per questo bundle.');
        }

        DB::table('bundle_plans')->where('id', $id)->where('tenant_id', $tenantId)->delete();
        return back()->with('success', 'Piano bundle eliminato.');
    }

    public function bundleToggle(string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $plan = DB::table('bundle_plans')->where('id', $id)->where('tenant_id', $tenantId)->first();
        if (!$plan) abort(404);

        DB::table('bundle_plans')
            ->where('id', $id)
            ->update(['is_active' => !$plan->is_active, 'updated_at' => now()]);

        $label = $plan->is_active ? 'disattivato' : 'attivato';
        return back()->with('success', "Piano bundle {$label}.");
    }

    public function bundlesIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $bundlePlans  = DB::table('bundle_plans')->where('tenant_id', $tenantId)->orderBy('name')->get();
        $subscriptions = DB::table('bundle_subscriptions')
            ->join('bundle_plans', 'bundle_subscriptions.bundle_plan_id', '=', 'bundle_plans.id')
            ->join('customers', 'bundle_subscriptions.customer_id', '=', 'customers.id')
            ->where('bundle_subscriptions.tenant_id', $tenantId)
            ->selectRaw("bundle_subscriptions.*,
                bundle_plans.name AS bundle_name,
                COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS customer_full_name")
            ->where('bundle_subscriptions.status', 'active')
            ->paginate(25)->withQueryString();

        return view('billing.bundles.index', compact('bundlePlans', 'subscriptions'));
    }

    // ── Payment Matching ──────────────────────────────────────────────────────

    public function paymentMatchingIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $rules = DB::table('payment_matching_rules')
            ->where('tenant_id', $tenantId)
            ->orderBy('priority')
            ->get();

        return view('billing.payment-matching', compact('rules'));
    }

    // ── Dunning ───────────────────────────────────────────────────────────────

    public function dunningIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $policies = DB::table('dunning_policies')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $stats = [
            'active'    => DB::table('dunning_runs')->where('tenant_id', $tenantId)->where('status', 'running')->count(),
            'suspended' => DB::table('dunning_runs')->where('tenant_id', $tenantId)->where('last_action', 'suspend')->whereDate('updated_at', today())->count(),
            'resolved'  => DB::table('dunning_runs')->where('tenant_id', $tenantId)->where('status', 'resolved')->whereMonth('updated_at', now()->month)->count(),
        ];

        $activeRuns = DB::table('dunning_runs as dr')
            ->leftJoin('dunning_policies as dp', 'dr.dunning_policy_id', '=', 'dp.id')
            ->leftJoin('contracts as ct', 'dr.contract_id', '=', 'ct.id')
            ->leftJoin('customers as cu', 'ct.customer_id', '=', 'cu.id')
            ->where('dr.tenant_id', $tenantId)
            ->where('dr.status', 'running')
            ->selectRaw("dr.*,
                dp.name AS policy_name,
                ct.code AS contract_code,
                COALESCE(cu.company_name, cu.first_name || ' ' || cu.last_name) AS customer_full_name")
            ->orderBy('dr.updated_at')
            ->limit(50)
            ->get();

        return view('billing.dunning.index', compact('policies', 'stats', 'activeRuns'));
    }

    // ── SEPA SDD ──────────────────────────────────────────────────────────────

    public function sepaIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // File SEPA generati
        $sepaFiles = DB::table('sepa_files')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        // Fatture SDD in scadenza non ancora in un batch (payment_method='sdd', status issued/overdue)
        $dueSddInvoices = DB::table('invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->where('i.tenant_id', $tenantId)
            ->whereIn('i.status', ['issued', 'overdue'])
            ->whereRaw("i.id NOT IN (
                SELECT p.invoice_id FROM payments p
                WHERE p.tenant_id = ? AND p.method = 'sdd' AND p.status IN ('pending','completed')
            )", [$tenantId])
            ->whereExists(function ($q) {
                $q->from('sepa_mandates as sm')
                  ->whereColumn('sm.customer_id', 'i.customer_id')
                  ->where('sm.status', 'active');
            })
            ->selectRaw("i.id, i.number, i.total, i.due_date, i.issue_date,
                COALESCE(c.company_name, c.first_name || ' ' || c.last_name) AS customer_name")
            ->orderBy('i.due_date')
            ->get();

        // Mandati SDD attivi
        $mandateStats = DB::table('sepa_mandates')
            ->where('tenant_id', $tenantId)
            ->selectRaw("status, COUNT(*) AS cnt")
            ->groupBy('status')
            ->get()->keyBy('status');

        // KPI
        $kpis = (object)[
            'active_mandates'  => $mandateStats->get('active')?->cnt   ?? 0,
            'pending_files'    => DB::table('sepa_files')->where('tenant_id', $tenantId)->whereIn('status', ['generated','submitted'])->count(),
            'due_invoices'     => $dueSddInvoices->count(),
            'due_total'        => $dueSddInvoices->sum('total'),
        ];

        return view('billing.sepa.index', compact('sepaFiles', 'dueSddInvoices', 'mandateStats', 'kpis'));
    }

    public function sepaGenerate(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Carica le fatture SDD eleggibili come modelli Eloquent
        $invoices = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['issued', 'overdue'])
            ->whereRaw("id NOT IN (
                SELECT invoice_id FROM payments
                WHERE tenant_id = ? AND method = 'sdd' AND status IN ('pending','completed')
            )", [$tenantId])
            ->whereHas('customer.sepaMandates', fn($q) => $q->where('status', 'active'))
            ->with(['customer'])
            ->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'Nessuna fattura SDD eleggibile per la generazione del batch.');
        }

        try {
            $sepaFile = app(SddService::class)->generateBatch($invoices);
            return back()->with('success',
                "Batch SEPA generato: {$sepaFile->filename} — {$sepaFile->transaction_count} transazioni — € " .
                number_format($sepaFile->control_sum, 2, ',', '.')
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore generazione SEPA: ' . $e->getMessage());
        }
    }

    public function sepaImportReturn(Request $request)
    {
        $request->validate([
            'return_file' => ['required', 'file', 'mimes:xml', 'max:10240'],
        ]);

        $xmlContent = file_get_contents($request->file('return_file')->getRealPath());

        try {
            $result = app(SddService::class)->processReturnFile($xmlContent);
            return back()->with('success',
                "File R-transaction elaborato: {$result['processed']} transazioni processate, {$result['errors']} errori."
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore import file ritorno: ' . $e->getMessage());
        }
    }

    // ── Fatturazione manuale ──────────────────────────────────────────────────

    public function billingRunIndex()
    {
        $tenantId = auth()->user()->tenant_id;

        // Ultime generazioni per questo tenant (da log fatture)
        $recentRuns = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw("TO_CHAR(issue_date, 'YYYY-MM') AS month,
                COUNT(*) AS invoice_count,
                SUM(total) AS total_amount,
                MAX(created_at) AS run_at")
            ->groupByRaw("TO_CHAR(issue_date, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(issue_date, 'YYYY-MM') DESC")
            ->limit(12)
            ->get();

        // Contratti attivi
        $activeContracts = DB::table('contracts')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        // Mesi disponibili per selezione
        $months = [];
        for ($i = 0; $i <= 11; $i++) {
            $d = now()->subMonths($i);
            $months[$d->format('Y-m')] = $d->translatedFormat('F Y');
        }

        return view('billing.run.index', compact('recentRuns', 'activeContracts', 'months'));
    }

    public function billingRunGenerate(Request $request)
    {
        $request->validate([
            'month'   => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $dryRun = $request->boolean('dry_run');
        $month  = $request->input('month');

        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call(
                'billing:generate-monthly',
                array_filter([
                    '--month'   => $month,
                    '--tenant'  => auth()->user()->tenant_id,
                    '--dry-run' => $dryRun ?: null,
                ])
            );

            $output = trim(\Illuminate\Support\Facades\Artisan::output());

            if ($exitCode !== 0) {
                return back()->with('error', 'Errore durante la generazione: ' . $output);
            }

            $label = $dryRun ? 'Simulazione completata' : 'Generazione completata';
            return back()->with('success', "{$label} per {$month}. Output: {$output}");

        } catch (\Throwable $e) {
            return back()->with('error', 'Errore: ' . $e->getMessage());
        }
    }

    // ── Payments ─────────────────────────────────────────────────────────────

    public function paymentIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $payments = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('contracts', 'invoices.contract_id', '=', 'contracts.id')
            ->join('customers', 'contracts.customer_id', '=', 'customers.id')
            ->where('payments.tenant_id', $tenantId)
            ->selectRaw("payments.*, invoices.number AS invoice_number,
                COALESCE(customers.company_name, customers.first_name || ' ' || customers.last_name) AS customer_full_name")
            ->orderByDesc('payments.created_at')
            ->paginate(25);

        return view('billing.payments.index', compact('payments'));
    }
}
