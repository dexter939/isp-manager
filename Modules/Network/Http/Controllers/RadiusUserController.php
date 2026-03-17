<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Network\Models\RadiusUser;
use Modules\Network\Services\CoaService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RadiusUserController extends Controller
{
    public function __construct(
        private readonly CoaService $coaService,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RadiusUser::class);

        $users = QueryBuilder::for(RadiusUser::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('walled_garden'),
            ])
            ->allowedSorts(['username', 'last_auth_at', 'created_at'])
            ->defaultSort('-last_auth_at')
            ->with(['customer', 'profile'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->paginate($request->integer('per_page', 20));

        return response()->json($users);
    }

    public function show(RadiusUser $radiusUser): JsonResponse
    {
        $this->authorize('view', $radiusUser);
        $radiusUser->load(['customer', 'contract', 'profile', 'sessions' => fn($q) => $q->active()]);
        return response()->json(['data' => $radiusUser]);
    }

    /**
     * Forza il walled garden manualmente (operatore).
     */
    public function suspend(RadiusUser $radiusUser): JsonResponse
    {
        $this->authorize('update', $radiusUser);
        $this->coaService->suspendToWalledGarden($radiusUser);
        return response()->json(['data' => $radiusUser->fresh()]);
    }

    /**
     * Ripristina l'accesso manualmente (operatore).
     */
    public function restore(RadiusUser $radiusUser): JsonResponse
    {
        $this->authorize('update', $radiusUser);
        $this->coaService->restoreAccess($radiusUser);
        return response()->json(['data' => $radiusUser->fresh()]);
    }

    /**
     * Lista sessioni (accounting) con Decreto Pisanu compliance.
     */
    public function sessions(Request $request, RadiusUser $radiusUser): JsonResponse
    {
        $this->authorize('view', $radiusUser);

        $sessions = $radiusUser->sessions()
            ->orderByDesc('acct_start')
            ->paginate($request->integer('per_page', 50));

        return response()->json($sessions);
    }
}
