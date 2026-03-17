<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Sdi\Models\SdiTransmission;
use Modules\Billing\Sdi\Services\SdiTransmissionService;
use Modules\Billing\Sdi\Exceptions\SdiTransmissionException;
use Modules\Billing\Sdi\Exceptions\SdiValidationException;

class SdiWebController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('sdi_transmissions as st')
            ->join('invoices as i',   'i.id',  '=', 'st.invoice_id')
            ->join('customers as cu', 'cu.id', '=', 'i.customer_id')
            ->where('i.tenant_id', $tenantId)
            ->select(
                'st.id',
                'st.uuid',
                'st.channel',
                'st.status',
                'st.filename',
                'st.notification_code',
                'st.retry_count',
                'st.last_error',
                'st.sent_at',
                'st.created_at',
                'i.id   as invoice_id',
                'i.number as invoice_number',
                'i.total',
                'i.issue_date',
                DB::raw("cu.first_name || ' ' || cu.last_name as customer_name"),
                'cu.company_name'
            );

        if ($request->filled('status')) {
            $query->where('st.status', $request->status);
        }
        if ($request->filled('channel')) {
            $query->where('st.channel', $request->channel);
        }
        if ($request->filled('from')) {
            $query->whereDate('st.created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('st.created_at', '<=', $request->to);
        }

        $transmissions = $query->orderByDesc('st.created_at')->paginate(25)->withQueryString();

        // KPI
        $kpis = DB::table('sdi_transmissions as st')
            ->join('invoices as i', 'i.id', '=', 'st.invoice_id')
            ->where('i.tenant_id', $tenantId)
            ->selectRaw("
                count(*)                                filter (where st.status = 'pending')   as pending_count,
                count(*)                                filter (where st.status = 'sent')      as sent_count,
                count(*)                                filter (where st.status = 'delivered') as delivered_count,
                count(*)                                filter (where st.status = 'accepted')  as accepted_count,
                count(*)                                filter (where st.status = 'rejected')  as rejected_count,
                count(*)                                filter (where st.status = 'error')     as error_count
            ")
            ->first();

        // Count invoices still waiting for first transmission
        $toTransmitCount = DB::table('invoices as i')
            ->leftJoin('sdi_transmissions as st', function ($join) {
                $join->on('st.invoice_id', '=', 'i.id')
                     ->where('st.status', 'accepted');
            })
            ->where('i.tenant_id', $tenantId)
            ->whereNotIn('i.status', ['draft', 'cancelled'])
            ->whereNull('st.id')
            ->count();

        return view('billing.sdi.index', compact('transmissions', 'kpis', 'toTransmitCount'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $transmission = DB::table('sdi_transmissions as st')
            ->join('invoices as i',   'i.id',  '=', 'st.invoice_id')
            ->join('customers as cu', 'cu.id', '=', 'i.customer_id')
            ->where('st.id', $id)
            ->where('i.tenant_id', $tenantId)
            ->selectRaw("st.*, i.number as invoice_number, i.total as invoice_total,
                         i.issue_date, cu.first_name || ' ' || cu.last_name as customer_name,
                         cu.company_name, cu.id as customer_id, i.id as invoice_id_link")
            ->first();

        abort_if(! $transmission, 404);

        $notifications = DB::table('sdi_notifications')
            ->where('transmission_id', $id)
            ->orderByDesc('received_at')
            ->get();

        $canRetry = ! in_array($transmission->status, ['accepted', 'rejected'])
            && $transmission->retry_count < config('sdi.max_retries', 3);

        return view('billing.sdi.show', compact('transmission', 'notifications', 'canRetry'));
    }

    // ── Retry ─────────────────────────────────────────────────────────────────

    public function retry(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $transmission = SdiTransmission::whereHas('invoice', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->findOrFail($id);

        $service = app(SdiTransmissionService::class);

        try {
            $service->retry($transmission);
        } catch (SdiTransmissionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Trasmissione inviata nuovamente al Sistema di Interscambio.');
    }

    // ── Batch ─────────────────────────────────────────────────────────────────

    public function batch(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Invoices that don't have an accepted transmission yet
        $query = DB::table('invoices as i')
            ->join('customers as cu', 'cu.id', '=', 'i.customer_id')
            ->leftJoin('sdi_transmissions as st', function ($join) {
                $join->on('st.invoice_id', '=', 'i.id')
                     ->where('st.status', 'accepted');
            })
            ->where('i.tenant_id', $tenantId)
            ->whereNotIn('i.status', ['draft', 'cancelled'])
            ->whereNull('st.id')
            ->select(
                'i.id', 'i.number', 'i.total', 'i.issue_date', 'i.status as invoice_status',
                DB::raw("cu.first_name || ' ' || cu.last_name as customer_name"),
                'cu.company_name',
                DB::raw("(SELECT st2.status FROM sdi_transmissions st2 WHERE st2.invoice_id = i.id ORDER BY st2.created_at DESC LIMIT 1) as last_sdi_status")
            );

        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('i.number', 'ilike', $q)
                    ->orWhere('cu.company_name', 'ilike', $q)
                    ->orWhere('cu.first_name', 'ilike', $q)
                    ->orWhere('cu.last_name', 'ilike', $q);
            });
        }
        if ($request->filled('year')) {
            $query->whereYear('i.issue_date', $request->year);
        }

        $invoices = $query->orderByDesc('i.issue_date')->paginate(30)->withQueryString();

        $years = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->selectRaw('EXTRACT(YEAR FROM issue_date)::int AS y')
            ->groupByRaw('1')
            ->orderByDesc('y')
            ->pluck('y');

        return view('billing.sdi.batch', compact('invoices', 'years'));
    }

    public function batchTransmit(Request $request)
    {
        $request->validate(['invoice_ids' => 'required|array|min:1', 'invoice_ids.*' => 'integer']);

        $tenantId = auth()->user()->tenant_id;
        $service  = app(SdiTransmissionService::class);

        $sent   = 0;
        $errors = [];

        foreach ($request->invoice_ids as $invoiceId) {
            // Verify ownership
            $exists = DB::table('invoices')
                ->where('id', $invoiceId)
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->exists();

            if (! $exists) {
                continue;
            }

            try {
                $invoice = Invoice::findOrFail($invoiceId);
                $service->send($invoice);
                $sent++;
            } catch (SdiTransmissionException | SdiValidationException $e) {
                $errors[] = "Fattura #{$invoiceId}: " . $e->getMessage();
            } catch (\Throwable $e) {
                $errors[] = "Fattura #{$invoiceId}: " . $e->getMessage();
            }
        }

        $msg = "Trasmesse {$sent} fatture al Sistema di Interscambio.";
        if ($errors) {
            $msg .= ' Errori: ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $msg .= ' e altri ' . (count($errors) - 3) . '.';
            }
        }

        return redirect()->route('billing.sdi.index')
            ->with($errors ? 'warning' : 'success', $msg);
    }
}
