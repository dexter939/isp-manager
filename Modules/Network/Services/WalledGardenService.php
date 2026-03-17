<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Network\Models\RadiusUser;

/**
 * Gestisce il portale Walled Garden per i clienti morosi.
 *
 * Quando il cliente apre il browser in walled garden viene rediretto a:
 *   https://paga.{isp}.it/{token}
 *
 * Il token è univoco per sessione e ha TTL 72h (renovabile).
 * Dopo il pagamento, CoaService::restoreAccess() sblocca l'accesso.
 */
class WalledGardenService
{
    private const TOKEN_TTL_HOURS = 72;

    /**
     * Genera o recupera il URL del portale di pagamento per un cliente sospeso.
     */
    public function getPortalUrl(RadiusUser $radiusUser): string
    {
        $token = $radiusUser->walled_garden_token;

        if (!$token) {
            $token = bin2hex(random_bytes(16));
            $radiusUser->update(['walled_garden_token' => $token]);
        }

        $baseUrl = config('network.walled_garden_url', 'https://paga.isp.local');
        return "{$baseUrl}/{$token}";
    }

    /**
     * Verifica se un token walled garden è valido e restituisce l'utente RADIUS.
     */
    public function resolveToken(string $token): ?RadiusUser
    {
        return RadiusUser::where('walled_garden_token', $token)
            ->where('walled_garden', true)
            ->where('status', 'suspended')
            ->first();
    }

    /**
     * Invalida il token dopo il pagamento.
     */
    public function invalidateToken(RadiusUser $radiusUser): void
    {
        $radiusUser->update(['walled_garden_token' => null]);
        Cache::forget("walled_garden:{$radiusUser->id}");
    }

    /**
     * Restituisce le regole RADIUS per il Walled Garden.
     * Queste vengono incluse nel profilo RADIUS durante la sospensione.
     *
     * @return array<string, string>
     */
    public function getRadiusAttributes(): array
    {
        $redirectUrl = config('network.walled_garden_url', 'https://paga.isp.local');

        return [
            'WISPr-Redirect-URL'            => $redirectUrl,
            'Reply-Message'                 => 'Accesso sospeso per morosita. Visita: ' . $redirectUrl,
            'Mikrotik-Recv-Limit'           => '10000000',       // 10MB download cap
            'Mikrotik-Xmit-Limit'           => '5000000',        // 5MB upload cap
        ];
    }
}
