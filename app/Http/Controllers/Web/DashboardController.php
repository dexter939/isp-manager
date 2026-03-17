<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        // KPI counts
        $stats = [
            'active_contracts' => DB::table('contracts')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count(),

            'monthly_revenue' => DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->sum('total_amount'),

            'open_tickets' => DB::table('trouble_tickets')
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),

            'network_alerts' => DB::table('network_alerts')
                ->where('tenant_id', $tenantId)
                ->whereNull('resolved_at')
                ->count(),

            'contracts_by_status' => DB::table('contracts')
                ->where('tenant_id', $tenantId)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray(),
        ];

        $overdueInvoices = DB::table('invoices')
            ->join('contracts', 'invoices.contract_id', '=', 'contracts.id')
            ->join('customers', 'contracts.customer_id', '=', 'customers.id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.status', 'overdue')
            ->select('invoices.*', 'customers.first_name', 'customers.last_name', 'customers.company_name')
            ->orderBy('invoices.due_date')
            ->limit(5)
            ->get();

        $recentTickets = DB::table('trouble_tickets')
            ->join('contracts', 'trouble_tickets.contract_id', '=', 'contracts.id')
            ->join('customers', 'contracts.customer_id', '=', 'customers.id')
            ->where('trouble_tickets.tenant_id', $tenantId)
            ->select('trouble_tickets.*', 'customers.first_name', 'customers.last_name')
            ->orderByDesc('trouble_tickets.created_at')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact('stats', 'overdueInvoices', 'recentTickets'));
    }
}
