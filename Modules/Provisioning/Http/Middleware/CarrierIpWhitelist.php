<?php

declare(strict_types=1);

namespace Modules\Provisioning\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware: valida che la richiesta provenga da un IP carrier autorizzato.
 * Configurato in config/provisioning.php per carrier.
 */
class CarrierIpWhitelist
{
    public function handle(Request $request, Closure $next, string $carrier): mixed
    {
        $allowedIps = config("provisioning.{$carrier}.webhook_ip_whitelist", []);

        // In modalità mock/test: bypass whitelist
        if (config('app.carrier_mock') || app()->environment('testing')) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps, true)) {
            Log::warning("Webhook {$carrier}: IP non autorizzato {$clientIp}", [
                'allowed' => $allowedIps,
                'path'    => $request->path(),
            ]);

            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
