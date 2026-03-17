<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
        public readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function render(): JsonResponse
    {
        $payload = ['message' => $this->getMessage()];

        if (!empty($this->errors)) {
            $payload['errors'] = $this->errors;
        }

        return response()->json($payload, $this->statusCode);
    }
}
