<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\DunningManager\Models\DunningCase;

final class DunningCaseOpened
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly DunningCase $model,
    ) {}
}
