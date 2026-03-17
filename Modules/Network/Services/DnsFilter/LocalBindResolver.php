<?php

declare(strict_types=1);

namespace Modules\Network\Services\DnsFilter;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Network\Models\ParentalControlProfile;
use Modules\Network\Models\ParentalControlSubscription;

/**
 * Stub DNS resolver for local BIND9/named-based filtering.
 * Actual BIND zone file generation and reloading is handled at the
 * infrastructure level (Ansible/salt). This service only logs the intent.
 */
class LocalBindResolver implements DnsFilterResolverInterface
{
    public function syncProfile(ParentalControlProfile $profile): bool
    {
        Log::info("[LocalBind] syncProfile — profile_id={$profile->id} name={$profile->name}");

        return true;
    }

    public function syncSubscription(ParentalControlSubscription $subscription): bool
    {
        Log::info(
            "[LocalBind] syncSubscription — subscription_id={$subscription->id} status={$subscription->status->value}"
        );

        return true;
    }

    public function getStats(ParentalControlSubscription $subscription, Carbon $from, Carbon $to): array
    {
        Log::info(
            "[LocalBind] getStats — subscription_id={$subscription->id} from={$from} to={$to}"
        );

        return [
            'subscription_id' => $subscription->id,
            'from'            => $from->toIso8601String(),
            'to'              => $to->toIso8601String(),
            'total_queries'   => 0,
            'blocked_queries' => 0,
            'allowed_queries' => 0,
            'top_blocked'     => [],
            'note'            => 'LocalBind stub — stats not available',
        ];
    }
}
