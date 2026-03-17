<?php

declare(strict_types=1);

namespace Modules\Coverage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Coverage\Http\Requests\FeasibilityRequest;
use Modules\Coverage\Http\Requests\NormalizeAddressRequest;
use Modules\Coverage\Services\AddressNormalizer;
use Modules\Coverage\Services\FeasibilityService;

class FeasibilityController extends Controller
{
    public function __construct(
        private readonly FeasibilityService $feasibility,
        private readonly AddressNormalizer $normalizer,
    ) {}

    /**
     * GET /api/v1/coverage/feasibility
     * Verifica copertura per un indirizzo.
     * Zero chiamate API carrier — solo DB locale.
     * Cache 7 giorni.
     *
     * @example GET /api/v1/coverage/feasibility?via=Via+Roma&civico=10&comune=Bari&provincia=BA
     */
    public function check(FeasibilityRequest $request): JsonResponse
    {
        $result = $this->feasibility->check(
            via: $request->validated('via'),
            civico: $request->validated('civico'),
            comune: $request->validated('comune'),
            provincia: $request->validated('provincia'),
        );

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    /**
     * POST /api/v1/coverage/normalize
     * Normalizza un indirizzo per uniformarlo al formato della banca dati.
     */
    public function normalize(NormalizeAddressRequest $request): JsonResponse
    {
        $normalized = $this->normalizer->normalizeAddress(
            via: $request->validated('via'),
            civico: $request->validated('civico'),
            comune: $request->validated('comune', ''),
            provincia: $request->validated('provincia', ''),
        );

        return response()->json(['data' => $normalized]);
    }

    /**
     * GET /api/v1/coverage/map
     * Ritorna GeoJSON per la mappa Leaflet.
     *
     * @example GET /api/v1/coverage/map?provincia=BA&tecnologia=FTTH
     */
    public function map(NormalizeAddressRequest $request): JsonResponse
    {
        $provincia  = strtoupper($request->query('provincia', ''));
        $tecnologia = $request->query('tecnologia');

        if (strlen($provincia) !== 2) {
            return response()->json(['error' => 'Parametro provincia obbligatorio (es: BA)'], 422);
        }

        $geoJson = $this->feasibility->getMapGeoJson($provincia, $tecnologia);

        return response()->json($geoJson);
    }
}
