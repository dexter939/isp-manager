<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contracts\Enums\CustomerStatus;
use Modules\Contracts\Http\Requests\StoreCustomerRequest;
use Modules\Contracts\Http\Resources\CustomerResource;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Services\CustomerService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('can:viewAny,Modules\Contracts\Models\Customer')->only(['index']);
        $this->middleware('can:view,customer')->only(['show']);
        $this->middleware('can:update,customer')->only(['update']);
        $this->middleware('can:delete,customer')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $customers = QueryBuilder::for(
            Customer::where('tenant_id', $request->user()->tenant_id)
                    ->with(['contracts' => fn($q) => $q->active()])
        )
        ->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::exact('type'),
            AllowedFilter::partial('email'),
            AllowedFilter::scope('search', 'search'),
        ])
        ->allowedSorts(['created_at', 'cognome', 'ragione_sociale', 'email'])
        ->defaultSort('-created_at')
        ->paginate($request->integer('per_page', 20));

        return response()->json(CustomerResource::collection($customers));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->service->create(
                $request->validated(),
                $request->user()->tenant_id
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new CustomerResource($customer), 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load([
            'contracts.servicePlan',
            'documents',
        ]);

        return response()->json(new CustomerResource($customer));
    }

    public function update(StoreCustomerRequest $request, Customer $customer): JsonResponse
    {
        try {
            $updated = $this->service->update($customer, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new CustomerResource($updated));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        // Soft delete — verifica che non abbia contratti attivi
        if ($customer->activeContracts()->exists()) {
            return response()->json([
                'message' => 'Impossibile eliminare un cliente con contratti attivi.',
            ], 409);
        }

        $customer->delete();

        return response()->json(null, 204);
    }

    /** Sospende il cliente (chiamato da dunning) */
    public function suspend(Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);
        $this->service->suspend($customer);

        return response()->json(['message' => 'Cliente sospeso.']);
    }
}
