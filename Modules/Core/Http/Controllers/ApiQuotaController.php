<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\ApiQuotaManager;

class ApiQuotaController extends Controller
{
    public function __construct(
        private readonly ApiQuotaManager $quotaManager,
    ) {}

    /**
     * Ritorna lo stato attuale di tutte le quote API carrier.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => $this->quotaManager->getAllQuotaStatus(),
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * Resetta il contatore Redis per un carrier/callType specifico.
     * Solo per admin in emergenza.
     */
    public function reset(string $carrier, string $callType): JsonResponse
    {
        $this->quotaManager->resetCounter($carrier, $callType);

        activity()
            ->withProperties(['carrier' => $carrier, 'call_type' => $callType])
            ->log('api_quota_reset');

        return response()->json([
            'message' => "Quota reset for {$carrier}/{$callType}",
        ]);
    }
}
