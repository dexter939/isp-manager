<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class ApiController extends BaseController
{
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function created(mixed $data): JsonResponse
    {
        return response()->json($data, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
