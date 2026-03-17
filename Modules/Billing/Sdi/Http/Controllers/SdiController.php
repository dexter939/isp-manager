<?php

namespace Modules\Billing\Sdi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Sdi\Models\SdiTransmission;
use Modules\Billing\Sdi\Services\SdiTransmissionService;

class SdiController extends ApiController
{
    public function __construct(private readonly SdiTransmissionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $query = SdiTransmission::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $query]);
    }

    public function transmit(int $invoiceId): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $channel = config('sdi.channel', 'aruba');
        $transmission = $this->service->send($invoice, $channel);

        return response()->json([
            'data'    => $transmission,
            'message' => 'Fattura trasmessa a SDI.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $transmission = SdiTransmission::with('notifications')->findOrFail($id);
        return response()->json(['data' => $transmission]);
    }

    public function retry(int $id): JsonResponse
    {
        $transmission = SdiTransmission::findOrFail($id);
        $this->service->retry($transmission);

        return response()->json(['message' => 'Retry avviato.']);
    }
}
