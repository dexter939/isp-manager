<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Exceptions;

use App\Exceptions\ApiException;

class SdiValidationException extends ApiException
{
    public static function xsdValidationFailed(string $errors): self
    {
        return new self("FatturaPA XML failed XSD validation: {$errors}", 422);
    }

    public static function invalidHmacSignature(): self
    {
        return new self('Invalid HMAC signature on SDI webhook request.', 401);
    }

    public static function missingInvoiceData(string $field): self
    {
        return new self("Invoice is missing required field for FatturaPA generation: {$field}", 422);
    }
}
