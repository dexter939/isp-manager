<?php

declare(strict_types=1);

namespace Modules\Network\Services\DnsFilter;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Modules\Network\Models\ParentalControlProfile;
use Modules\Network\Models\ParentalControlSubscription;

class WhaleboneResolver implements DnsFilterResolverInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('parental_control.whalebone_api_url'),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('parental_control.whalebone_api_key'),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'timeout' => 10,
        ]);
    }

    public function syncProfile(ParentalControlProfile $profile): bool
    {
        if (config('app.carrier_mock')) {
            Log::info("[MOCK] WhaleboneResolver::syncProfile — profile_id={$profile->id}");
            return true;
        }

        try {
            $response = $this->client->post('/profiles', [
                'json' => [
                    'id'                 => $profile->id,
                    'name'               => $profile->name,
                    'blocked_categories' => $profile->blocked_categories,
                    'custom_blacklist'   => $profile->custom_blacklist,
                    'custom_whitelist'   => $profile->custom_whitelist,
                    'agcom_compliant'    => $profile->agcom_compliant,
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException(
                    "Whalebone syncProfile HTTP error {$response->getStatusCode()}: {$response->getBody()}"
                );
            }

            return true;
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Whalebone syncProfile failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function syncSubscription(ParentalControlSubscription $subscription): bool
    {
        if (config('app.carrier_mock')) {
            Log::info("[MOCK] WhaleboneResolver::syncSubscription — subscription_id={$subscription->id}");
            return true;
        }

        try {
            $response = $this->client->post('/policies', [
                'json' => [
                    'id'                        => $subscription->id,
                    'profile_id'                => $subscription->profile_id,
                    'pppoe_account_id'          => $subscription->pppoe_account_id,
                    'status'                    => $subscription->status->value,
                    'customer_custom_blacklist' => $subscription->customer_custom_blacklist,
                    'customer_custom_whitelist' => $subscription->customer_custom_whitelist,
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException(
                    "Whalebone syncSubscription HTTP error {$response->getStatusCode()}: {$response->getBody()}"
                );
            }

            return true;
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Whalebone syncSubscription failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function getStats(ParentalControlSubscription $subscription, Carbon $from, Carbon $to): array
    {
        if (config('app.carrier_mock')) {
            Log::info("[MOCK] WhaleboneResolver::getStats — subscription_id={$subscription->id}");
            return [
                'subscription_id' => $subscription->id,
                'from'            => $from->toIso8601String(),
                'to'              => $to->toIso8601String(),
                'total_queries'   => 0,
                'blocked_queries' => 0,
                'allowed_queries' => 0,
                'top_blocked'     => [],
            ];
        }

        try {
            $response = $this->client->get("/stats/{$subscription->id}", [
                'query' => [
                    'from' => $from->toIso8601String(),
                    'to'   => $to->toIso8601String(),
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException(
                    "Whalebone getStats HTTP error {$response->getStatusCode()}: {$response->getBody()}"
                );
            }

            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Whalebone getStats failed: {$e->getMessage()}", 0, $e);
        }
    }
}
