<?php

namespace Modules\Billing\OnlinePayments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Billing\OnlinePayments\Services\NexiGateway;

class NexiCallbackController extends Controller
{
    public function handle(Request $request, NexiGateway $gateway): JsonResponse
    {
        try {
            $gateway->handleCallback($request->all());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'Invalid MAC'], 400);
        }

        return response()->json(['message' => 'OK']);
    }
}
