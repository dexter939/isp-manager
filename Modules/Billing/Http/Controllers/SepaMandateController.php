<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Billing\Http\Requests\StoreSepaMandateRequest;
use Modules\Billing\Models\SepaMandate;
use Modules\Contracts\Models\Customer;

class SepaMandateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Lista mandati SDD di un cliente.
     */
    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $mandates = SepaMandate::where('customer_id', $customer->id)
            ->latest()
            ->get();

        return response()->json(['data' => $mandates]);
    }

    /**
     * Registra un nuovo mandato SDD.
     */
    public function store(StoreSepaMandateRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $mandate = SepaMandate::create([
            'tenant_id'      => $request->user()->tenant_id,
            'customer_id'    => $customer->id,
            'mandate_id'     => 'MND-' . str_pad((string) (SepaMandate::max('id') + 1), 6, '0', STR_PAD_LEFT),
            'signed_at'      => $request->signed_at,
            'sequence_type'  => 'RCUR',
            'iban'           => $request->iban,
            'bic'            => $request->bic,
            'account_holder' => $request->account_holder,
            'creditor_id'    => config('app.sepa_creditor_id', ''),
            'status'         => 'active',
        ]);

        return response()->json(['data' => $mandate], 201);
    }

    /**
     * Revoca un mandato SDD.
     */
    public function revoke(Request $request, SepaMandate $mandate): JsonResponse
    {
        $this->authorize('update', $mandate->customer);

        $request->validate([
            'reason' => 'required|string|in:MS02,MD01,AC04,customer_request',
        ]);

        $mandate->revoke($request->reason);

        return response()->json(['data' => $mandate]);
    }
}
