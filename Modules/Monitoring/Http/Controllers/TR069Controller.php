<?php

declare(strict_types=1);

namespace Modules\Monitoring\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Monitoring\Http\Requests\RefreshParametersRequest;
use Modules\Monitoring\Http\Requests\SetParametersRequest;
use Modules\Monitoring\Services\TR069Service;
use Modules\Network\Models\CpeDevice;

class TR069Controller extends ApiController
{
    public function __construct(
        private readonly TR069Service $tr069,
    ) {
        $this->middleware('auth:sanctum')->except(['genieacsWebhook']);
    }

    /**
     * Lista parametri TR-069 di un CPE.
     */
    public function parameters(CpeDevice $device): JsonResponse
    {
        $this->authorize('view', $device);

        $params = $device->tr069Parameters()->orderBy('parameter_path')->get();
        return $this->success(['data' => $params]);
    }

    /**
     * Refresh parametri dal CPE (task GenieACS).
     */
    public function refresh(RefreshParametersRequest $request, CpeDevice $device): JsonResponse
    {
        $this->authorize('update', $device);

        $data   = $request->validated();
        $result = $this->tr069->getParameters($device, $data['parameters']);
        return $this->success(['data' => $result]);
    }

    /**
     * Imposta parametri sul CPE.
     */
    public function setParameters(SetParametersRequest $request, CpeDevice $device): JsonResponse
    {
        $this->authorize('update', $device);

        $data = $request->validated();
        $this->tr069->setParameters($device, $data['parameters']);
        return $this->success(['status' => 'queued']);
    }

    /**
     * Riavvia il CPE.
     */
    public function reboot(CpeDevice $device): JsonResponse
    {
        $this->authorize('update', $device);
        $this->tr069->reboot($device);
        return $this->success(['status' => 'reboot_queued']);
    }

    /**
     * Riceve l'Inform da GenieACS (webhook — no auth Sanctum).
     */
    public function genieacsWebhook(Request $request): JsonResponse
    {
        $deviceId   = $request->input('_id');
        $parameters = $request->input('_deviceParameters', []);

        if (!$deviceId) {
            return response()->json(['error' => 'Missing _id'], 400);
        }

        $this->tr069->processInform($deviceId, $parameters);
        return response()->json(['status' => 'ok']);
    }
}
