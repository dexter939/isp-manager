<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;
use Modules\Maintenance\Http\Requests\AddNoteRequest;
use Modules\Maintenance\Http\Requests\AssignTicketRequest;
use Modules\Maintenance\Http\Requests\ResolveTicketRequest;
use Modules\Maintenance\Http\Requests\StoreTicketRequest;
use Modules\Maintenance\Http\Requests\TransitionTicketRequest;
use Modules\Maintenance\Http\Resources\TroubleTicketResource;
use Modules\Maintenance\Models\TroubleTicket;
use Modules\Maintenance\Services\TicketService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TicketController extends ApiController
{
    public function __construct(
        private readonly TicketService $service,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $tickets = QueryBuilder::for(TroubleTicket::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('assigned_to'),
                AllowedFilter::scope('overdue'),
            ])
            ->allowedSorts(['opened_at', 'due_at', 'priority', 'status'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer'])
            ->paginate(25);

        return response()->json(TroubleTicketResource::collection($tickets));
    }

    public function show(TroubleTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'data' => new TroubleTicketResource($ticket->load(['customer', 'contract', 'notes'])),
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $data = $request->validated();

        $ticket = $this->service->create(
            tenantId:    auth()->user()->tenant_id,
            title:       $data['title'],
            description: $data['description'],
            priority:    TicketPriority::from($data['priority']),
            type:        $data['type'] ?? 'other',
            source:      'manual',
            customerId:  $data['customer_id'] ?? null,
            contractId:  $data['contract_id'] ?? null,
        );

        return response()->json(['data' => new TroubleTicketResource($ticket)], 201);
    }

    public function assign(AssignTicketRequest $request, TroubleTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $data = $request->validated();

        return response()->json([
            'data' => new TroubleTicketResource($this->service->assign($ticket, $data['user_id'], $data['note'] ?? null)),
        ]);
    }

    public function transition(TransitionTicketRequest $request, TroubleTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $data = $request->validated();

        return response()->json([
            'data' => new TroubleTicketResource($this->service->transition($ticket, TicketStatus::from($data['status']), $data['note'] ?? null)),
        ]);
    }

    public function resolve(ResolveTicketRequest $request, TroubleTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $data = $request->validated();

        return response()->json([
            'data' => new TroubleTicketResource($this->service->resolve($ticket, $data['resolution_notes'], auth()->id())),
        ]);
    }

    public function addNote(AddNoteRequest $request, TroubleTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $data = $request->validated();

        $note = $this->service->addNote(
            ticket:     $ticket,
            body:       $data['body'],
            userId:     auth()->id(),
            isInternal: (bool) ($data['is_internal'] ?? false),
        );

        return response()->json(['data' => $note], 201);
    }
}
