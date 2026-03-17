<?php

namespace Modules\Maintenance\FieldService\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Modules\Maintenance\FieldService\Events\InterventionCompleted;
use Modules\Maintenance\FieldService\Events\InterventionScheduled;
use Modules\Maintenance\FieldService\Models\FieldIntervention;

class FieldInterventionService
{
    public function __construct(private readonly TechnicianTracker $tracker) {}

    /**
     * Schedules a new field intervention.
     */
    public function schedule(array $data): FieldIntervention
    {
        $intervention = FieldIntervention::create($data);
        event(new InterventionScheduled($intervention));
        return $intervention;
    }

    /**
     * Marks intervention as started (in_progress).
     */
    public function startIntervention(FieldIntervention $intervention, float $lat, float $lon): void
    {
        $intervention->update(['status' => 'in_progress', 'started_at' => now(), 'latitude' => $lat, 'longitude' => $lon]);
        if ($intervention->technician_id) {
            $this->tracker->updatePosition($intervention->technician_id, $lat, $lon);
        }
    }

    /**
     * Completes an intervention.
     * Validates that at least one activity and one customer signature exists.
     * Generates verbale PDF and stores to MinIO.
     */
    public function complete(FieldIntervention $intervention): FieldIntervention
    {
        if ($intervention->activities()->count() === 0) {
            throw new \RuntimeException('Almeno un\'attività è richiesta per completare l\'intervento.');
        }

        $customerSignature = $intervention->signatures()->where('signer_type', 'customer')->first();
        if (!$customerSignature) {
            throw new \RuntimeException('La firma del cliente è obbligatoria per completare l\'intervento.');
        }

        $verbalePdf  = $this->generateVerbalePdf($intervention);
        $verbalePath = config('field_service.verbale_storage_path') . "/{$intervention->uuid}.pdf";

        Storage::disk(config('field_service.verbale_storage_disk', 'minio'))->put($verbalePath, $verbalePdf);

        $intervention->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'verbale_path' => $verbalePath,
        ]);

        event(new InterventionCompleted($intervention));

        return $intervention->fresh();
    }

    /**
     * Generates PDF verbale for an intervention.
     */
    public function getVerbalePdf(FieldIntervention $intervention): string
    {
        if ($intervention->verbale_path) {
            $content = Storage::disk(config('field_service.verbale_storage_disk', 'minio'))
                ->get($intervention->verbale_path);
            if ($content) return $content;
        }

        return $this->generateVerbalePdf($intervention);
    }

    private function generateVerbalePdf(FieldIntervention $intervention): string
    {
        $intervention->load(['activities', 'materials', 'photos', 'signatures']);

        $pdf = Pdf::loadView('field_service::verbale', ['intervention' => $intervention])
            ->setPaper('A4', 'portrait');

        return $pdf->output();
    }
}
