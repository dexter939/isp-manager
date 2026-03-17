<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrepaidWebController extends Controller
{
    // ── Wallets ───────────────────────────────────────────────────────────────

    public function walletsIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('prepaid_wallets as pw')
            ->join('customers as c', 'c.id', '=', 'pw.customer_id')
            ->where('pw.tenant_id', $tenantId)
            ->whereNull('pw.deleted_at')
            ->selectRaw("pw.id, pw.balance_amount, pw.balance_currency, pw.status,
                pw.low_balance_threshold_amount, pw.auto_suspend_on_zero, pw.created_at,
                COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS customer_name,
                c.id AS customer_id");

        if ($q = $request->input('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->whereRaw("LOWER(COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome)) LIKE ?", ['%'.strtolower($q).'%']);
            });
        }
        if ($status = $request->input('status')) {
            $query->where('pw.status', $status);
        }

        $wallets = $query->orderBy('customer_name')->paginate(25)->withQueryString();

        // KPI
        $kpis = DB::table('prepaid_wallets')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->selectRaw("COUNT(*) AS total,
                SUM(balance_amount) AS total_balance,
                SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN balance_amount <= low_balance_threshold_amount THEN 1 ELSE 0 END) AS low_count")
            ->first();

        return view('billing.prepaid.wallets.index', compact('wallets', 'kpis'));
    }

    public function walletShow(Request $request, string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $wallet = DB::table('prepaid_wallets as pw')
            ->join('customers as c', 'c.id', '=', 'pw.customer_id')
            ->where('pw.tenant_id', $tenantId)
            ->whereNull('pw.deleted_at')
            ->where('pw.id', $id)
            ->selectRaw("pw.*, COALESCE(c.ragione_sociale, c.nome || ' ' || c.cognome) AS customer_name, c.id AS customer_id")
            ->first();

        abort_if(!$wallet, 404);

        $transactions = DB::table('prepaid_transactions')
            ->where('wallet_id', $id)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(30);

        $orders = DB::table('prepaid_topup_orders as o')
            ->join('prepaid_topup_products as p', 'p.id', '=', 'o.product_id')
            ->where('o.wallet_id', $id)
            ->where('o.tenant_id', $tenantId)
            ->selectRaw("o.*, p.name AS product_name")
            ->orderByDesc('o.created_at')
            ->limit(15)
            ->get();

        return view('billing.prepaid.wallets.show', compact('wallet', 'transactions', 'orders'));
    }

    public function walletAdjust(Request $request, string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'direction'   => ['required', 'in:credit,debit'],
            'amount'      => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $wallet = DB::table('prepaid_wallets')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$wallet, 404);

        $amountCents  = (int) $request->input('amount');
        $direction    = $request->input('direction');
        $balanceBefore = $wallet->balance_amount;
        $balanceAfter  = $direction === 'credit'
            ? $balanceBefore + $amountCents
            : max(0, $balanceBefore - $amountCents);

        DB::transaction(function () use ($id, $tenantId, $amountCents, $direction, $balanceBefore, $balanceAfter, $request) {
            DB::table('prepaid_wallets')
                ->where('id', $id)
                ->update(['balance_amount' => $balanceAfter, 'updated_at' => now()]);

            DB::table('prepaid_transactions')->insert([
                'id'                  => Str::uuid(),
                'tenant_id'           => $tenantId,
                'wallet_id'           => $id,
                'type'                => 'admin_adjustment',
                'amount_amount'       => $amountCents,
                'amount_currency'     => $request->input('currency', 'EUR'),
                'direction'           => $direction,
                'balance_before_amount' => $balanceBefore,
                'balance_after_amount'  => $balanceAfter,
                'description'         => $request->input('description'),
                'payment_method'      => 'admin',
                'created_at'          => now(),
            ]);
        });

        return back()->with('success', 'Saldo aggiornato con successo.');
    }

    // ── Topup Products ────────────────────────────────────────────────────────

    public function productsIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $products = DB::table('prepaid_topup_products')
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('amount_amount')
            ->get();

        return view('billing.prepaid.products.index', compact('products'));
    }

    public function productStore(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'amount_amount'   => ['required', 'integer', 'min:100'],
            'bonus_amount'    => ['nullable', 'integer', 'min:0'],
            'validity_days'   => ['nullable', 'integer', 'min:1'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
        ]);

        DB::table('prepaid_topup_products')->insert([
            'id'              => Str::uuid(),
            'tenant_id'       => $tenantId,
            'name'            => $data['name'],
            'amount_amount'   => $data['amount_amount'],
            'amount_currency' => 'EUR',
            'bonus_amount'    => $data['bonus_amount'] ?? 0,
            'validity_days'   => $data['validity_days'] ?? null,
            'sort_order'      => $data['sort_order'] ?? 0,
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return back()->with('success', 'Prodotto creato.');
    }

    public function productUpdate(Request $request, string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'amount_amount' => ['required', 'integer', 'min:100'],
            'bonus_amount'  => ['nullable', 'integer', 'min:0'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        DB::table('prepaid_topup_products')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update([
                'name'          => $data['name'],
                'amount_amount' => $data['amount_amount'],
                'bonus_amount'  => $data['bonus_amount'] ?? 0,
                'validity_days' => $data['validity_days'] ?? null,
                'sort_order'    => $data['sort_order'] ?? 0,
                'updated_at'    => now(),
            ]);

        return back()->with('success', 'Prodotto aggiornato.');
    }

    public function productToggle(Request $request, string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $product = DB::table('prepaid_topup_products')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        abort_if(!$product, 404);

        DB::table('prepaid_topup_products')
            ->where('id', $id)
            ->update(['is_active' => !$product->is_active, 'updated_at' => now()]);

        $label = $product->is_active ? 'disattivato' : 'attivato';
        return back()->with('success', "Prodotto {$label}.");
    }

    public function productDestroy(string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('prepaid_topup_products')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->delete();

        return back()->with('success', 'Prodotto eliminato.');
    }
}
