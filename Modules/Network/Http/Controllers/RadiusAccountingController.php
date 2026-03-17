<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Network\Services\RadiusService;

/**
 * Riceve i pacchetti di accounting da FreeRADIUS via rlm_rest.
 *
 * FreeRADIUS è configurato con:
 *   accounting {
 *     rest {
 *       uri = "https://isp.local/api/v1/radius/accounting"
 *       method = "post"
 *     }
 *   }
 *
 * Auth: IP whitelist (middleware) + Bearer token fisso per FreeRADIUS.
 */
class RadiusAccountingController extends Controller
{
    public function __construct(
        private readonly RadiusService $radiusService,
    ) {}

    /**
     * Riceve Accounting-Start, Accounting-Stop, Accounting-Interim-Update.
     */
    public function handle(Request $request): JsonResponse
    {
        $attrs  = $request->all();
        $type   = $attrs['Acct-Status-Type'] ?? '';

        match($type) {
            'Start'           => $this->radiusService->accountingStart($attrs),
            'Stop'            => $this->radiusService->accountingStop($attrs),
            'Interim-Update'  => $this->handleInterimUpdate($attrs),
            default           => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleInterimUpdate(array $attrs): void
    {
        // Aggiorna il framed_ip dell'utente se cambiato
        \Modules\Network\Models\RadiusUser::where('acct_session_id', $attrs['Acct-Session-Id'] ?? '')
            ->update([
                'framed_ip'    => $attrs['Framed-IP-Address'] ?? null,
                'last_auth_at' => now(),
            ]);
    }
}
