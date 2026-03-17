<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Exceptions\ApiQuotaExceededException;
use Modules\Core\Services\ApiQuotaManager;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Middleware per bloccare chiamate API carrier se quota esaurita.
 * Uso: Route::middleware(['api.quota:openfiber,line_testing'])
 */
class CheckApiQuota
{
    public function __construct(
        private readonly ApiQuotaManager $quotaManager,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (SymfonyResponse) $next
     */
    public function handle(Request $request, Closure $next, string $carrier, string $callType, string $critical = 'false'): SymfonyResponse
    {
        try {
            $this->quotaManager->checkAndIncrement(
                carrier: $carrier,
                callType: $callType,
                isCritical: filter_var($critical, FILTER_VALIDATE_BOOLEAN),
            );
        } catch (ApiQuotaExceededException $e) {
            return response()->json([
                'error'   => 'api_quota_exceeded',
                'message' => $e->getMessage(),
                'carrier' => $e->carrier,
                'remaining' => $this->quotaManager->getRemainingCalls($carrier, $callType),
            ], SymfonyResponse::HTTP_TOO_MANY_REQUESTS);
        }

        return $next($request);
    }
}
