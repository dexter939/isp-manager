<?php

namespace Modules\Maintenance\FieldService\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Maintenance\FieldService\Http\Requests\UpdatePositionRequest;
use Modules\Maintenance\FieldService\Services\TechnicianTracker;

class TechnicianController extends ApiController
{
    public function __construct(private readonly TechnicianTracker $tracker) {}

    public function updatePosition(UpdatePositionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->tracker->updatePosition(
            $request->user()->id,
            (float) $validated['lat'],
            (float) $validated['lon'],
            isset($validated['accuracy']) ? (int) $validated['accuracy'] : null
        );
        return response()->json(['message' => 'Posizione aggiornata.']);
    }

    public function positions(): JsonResponse
    {
        return response()->json(['data' => $this->tracker->getAllPositions()]);
    }
}
