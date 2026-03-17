<?php

declare(strict_types=1);

namespace Modules\Contracts\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class SignatureResult extends Data
{
    public function __construct(
        public readonly bool $success,
        public readonly int $contractId,
        public readonly int $signatureId,
        public readonly Carbon $signedAt,
        public readonly string $pdfHashSha256,
        public readonly ?string $message = null,
    ) {}
}
