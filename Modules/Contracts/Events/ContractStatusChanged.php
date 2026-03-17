<?php

declare(strict_types=1);

namespace Modules\Contracts\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Contracts\Models\Contract;

class ContractStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Contract       $contract,
        public readonly ContractStatus $from,
        public readonly ContractStatus $to,
    ) {}
}
