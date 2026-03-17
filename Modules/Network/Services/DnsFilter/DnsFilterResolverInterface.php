<?php

declare(strict_types=1);

namespace Modules\Network\Services\DnsFilter;

use Carbon\Carbon;
use Modules\Network\Models\ParentalControlProfile;
use Modules\Network\Models\ParentalControlSubscription;

interface DnsFilterResolverInterface
{
    public function syncProfile(ParentalControlProfile $profile): bool;

    public function syncSubscription(ParentalControlSubscription $subscription): bool;

    public function getStats(ParentalControlSubscription $subscription, Carbon $from, Carbon $to): array;
}
