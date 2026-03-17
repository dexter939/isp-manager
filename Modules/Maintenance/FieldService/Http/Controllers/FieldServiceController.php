<?php

namespace Modules\Maintenance\FieldService\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maintenance\FieldService\Http\Requests\AddActivityRequest;
use Modules\Maintenance\FieldService\Http\Requests\AddMaterialRequest;
use Modules\Maintenance\FieldService\Http\Requests\SendOtpRequest;
use Modules\Maintenance\FieldService\Http\Requests\SignInterventionRequest;
use Modules\Maintenance\FieldService\Http\Requests\StartInterventionRequest;
use Modules\Maintenance\FieldService\Http\Requests\StoreFieldInterventionRequest;
use Modules\Maintenance\FieldService\Http\Requests\UploadPhotoRequest;
use Modules\Maintenance\FieldService\Models\FieldActivity;
use Modules\Maintenance\FieldService\Models\FieldIntervention;
use Modules\Maintenance\FieldService\Models\FieldMaterial;
use Modules\Maintenance\FieldService\Services\FieldInterventionService;
use Modules\Maintenance\FieldService\Services\FieldSignatureService;

class FieldServiceController extends ApiController
{
    public function __construct(
        private readonly FieldInterventionService $service,
        private readonly FieldSignatureService $signatureService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = FieldIntervention::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->technician_id, fn($q) => $q->where('technician_id', $request->technician_id))
            ->when($request->date, fn($q) => $q->whereDate('scheduled_at', $request->date))
            ->latest('scheduled_at')
            ->paginate(20);
        return $this->success(['data' => $items]);
    }

    public function store(StoreFieldInterventionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $intervention = $this->service->schedule($validated);
        return $this->created(['data' => $intervention, 'message' => 'Intervento pianificato.']);
    }

    public function show(string $uuid): JsonResponse
    {
        $item = FieldIntervention::with(['activities','materials','photos','signatures'])->where('uuid', $uuid)->firstOrFail();
        return $this->success(['data' => $item]);
    }

    public function start(StartInterventionRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $this->service->startIntervention($intervention, (float) $validated['lat'], (float) $validated['lon']);
        return $this->success(['message' => 'Intervento avviato.']);
    }

    public function addActivity(AddActivityRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $activity = FieldActivity::create(['intervention_id' => $intervention->id, ...$request->only('description','duration_minutes')]);
        return $this->created(['data' => $activity]);
    }

    public function addMaterial(AddMaterialRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $material = FieldMaterial::create(['intervention_id' => $intervention->id, ...$request->only('description','quantity','serial_number','inventory_item_id','unit_cost_cents')]);
        return $this->created(['data' => $material]);
    }

    public function uploadPhoto(UploadPhotoRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $path = $request->file('photo')->store(config('field_service.photos_storage_path'), config('field_service.photos_storage_disk', 'minio'));
        $photo = $intervention->photos()->create(['photo_path' => $path, 'taken_at' => now(), 'description' => $request->description]);
        return $this->created(['data' => $photo]);
    }

    public function complete(string $uuid): JsonResponse
    {
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $completed    = $this->service->complete($intervention);
        return $this->success(['data' => $completed, 'message' => 'Intervento completato.']);
    }

    public function verbale(string $uuid): \Symfony\Component\HttpFoundation\Response
    {
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $pdf          = $this->service->getVerbalePdf($intervention);
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"verbale_{$uuid}.pdf\"",
        ]);
    }

    public function sendOtp(SendOtpRequest $request, string $uuid): JsonResponse
    {
        $validated    = $request->validated();
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $otp          = $this->signatureService->sendOtp($intervention, $validated['phone']);
        $response     = ['message' => 'OTP inviato.'];
        if (config('app.carrier_mock', false)) $response['otp'] = $otp;
        return $this->success($response);
    }

    public function sign(SignInterventionRequest $request, string $uuid): JsonResponse
    {
        $validated    = $request->validated();
        $intervention = FieldIntervention::where('uuid', $uuid)->firstOrFail();
        $signature    = $this->signatureService->verifyAndSign(
            $intervention,
            $validated['otp'],
            $validated['signature_base64'],
            $validated['signer_name'],
            \Modules\Maintenance\FieldService\Enums\SignerType::Customer
        );
        return $this->success(['data' => $signature, 'message' => 'Firma acquisita.']);
    }
}
