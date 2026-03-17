<?php

declare(strict_types=1);

namespace Modules\Provisioning\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Contracts\Models\Contract;
use Modules\Provisioning\Enums\OrderType;
use Modules\Provisioning\Http\Requests\RescheduleOrderRequest;
use Modules\Provisioning\Http\Requests\StoreOrderRequest;
use Modules\Provisioning\Http\Resources\CarrierOrderResource;
use Modules\Provisioning\Jobs\SendActivationOrderJob;
use Modules\Provisioning\Jobs\SendDeactivationOrderJob;
use Modules\Provisioning\Models\CarrierOrder;
use Modules\Provisioning\Services\CarrierGateway;
use Modules\Provisioning\Services\OrderStateMachine;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends ApiController
{
    public function __construct(
        private readonly CarrierGateway    $gateway,
        private readonly OrderStateMachine $stateMachine,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $orders = QueryBuilder::for(
            CarrierOrder::where('tenant_id', $request->user()->tenant_id)
                ->with(['contract.customer'])
        )
        ->allowedFilters([
            AllowedFilter::exact('state'),
            AllowedFilter::exact('carrier'),
            AllowedFilter::exact('order_type'),
            AllowedFilter::exact('contract_id'),
        ])
        ->allowedSorts(['created_at', 'sent_at', 'scheduled_date'])
        ->defaultSort('-created_at')
        ->paginate($request->integer('per_page', 20));

        return $this->success(CarrierOrderResource::collection($orders));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $contract = Contract::findOrFail($validated['contract_id']);

        $order = CarrierOrder::create([
            'tenant_id'         => $request->user()->tenant_id,
            'contract_id'       => $contract->id,
            'carrier'           => $contract->carrier->value,
            'order_type'        => $validated['order_type'],
            'codice_ordine_olo' => $this->generateOloCode($request->user()->tenant_id),
        ]);

        return $this->created(new CarrierOrderResource($order));
    }

    public function show(CarrierOrder $order): JsonResponse
    {
        $order->load(['contract.customer', 'vlanPool', 'events' => fn($q) => $q->latest()->limit(20)]);
        return $this->success(new CarrierOrderResource($order));
    }

    /** Invia l'ordine al carrier (dispatch Job) */
    public function send(CarrierOrder $order): JsonResponse
    {
        $job = match ($order->order_type) {
            OrderType::Activation   => new SendActivationOrderJob($order->id),
            OrderType::Deactivation => new SendDeactivationOrderJob($order->id),
            default => null,
        };

        if (!$job) {
            return $this->error("Tipo ordine {$order->order_type->value} non ha un job di invio configurato.", 422);
        }

        dispatch($job)->onQueue('carrier-orders');

        return $this->success(['message' => 'Ordine in coda per invio al carrier.']);
    }

    /** Rimodula data appuntamento */
    public function reschedule(RescheduleOrderRequest $request, CarrierOrder $order): JsonResponse
    {
        $validated = $request->validated();

        $response = $this->gateway->sendReschedule($order, \Carbon\Carbon::parse($validated['date']));

        if ($response->isNack()) {
            return $this->error($response->errorMessage, 422);
        }

        return $this->success(['message' => 'Rimodulazione inviata al carrier.']);
    }

    /** Annulla l'ordine */
    public function cancel(CarrierOrder $order, Request $request): JsonResponse
    {
        try {
            $this->stateMachine->cancel($order, $request->input('reason', ''));
        } catch (\LogicException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(['message' => 'Ordine annullato.']);
    }

    /** Desospenzione linea */
    public function unsuspend(CarrierOrder $order): JsonResponse
    {
        $response = $this->gateway->sendUnsuspend($order);

        if ($response->isNack()) {
            return $this->error($response->errorMessage, 422);
        }

        return $this->success(['message' => 'Desospensione inviata al carrier.']);
    }

    /** Log eventi per un ordine */
    public function events(CarrierOrder $order): JsonResponse
    {
        $events = $order->events()->latest('logged_at')->limit(50)->get();
        return $this->success($events);
    }

    /** Genera codice OLO univoco: ISP-{ANNO}-{TENANT}-{SEQ} */
    private function generateOloCode(int $tenantId): string
    {
        $year = now()->format('Y');
        $seq  = str_pad((string) (CarrierOrder::count() + 1), 6, '0', STR_PAD_LEFT);
        return "ISP-{$year}-{$tenantId}-{$seq}";
    }
}
