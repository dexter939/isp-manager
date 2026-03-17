<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Sdi\Models\SdiTransmission;

class SdiPecChannel
{
    /**
     * Sends a FatturaPA XML transmission via PEC (Posta Elettronica Certificata).
     *
     * In mock/development mode, the XML is saved to storage('sdi-outbox/{filename}').
     * In production this would dispatch via PEC SMTP.
     * When config('app.carrier_mock') is true, the operation is logged only.
     *
     * @param  SdiTransmission $transmission The transmission record to send.
     * @return void
     */
    public function send(SdiTransmission $transmission): void
    {
        if (config('app.carrier_mock', false)) {
            Log::info('[SdiPecChannel] Mock mode — simulating PEC delivery.', [
                'transmission_id' => $transmission->id,
                'filename'        => $transmission->filename,
            ]);

            return;
        }

        $outboxPath = "sdi-outbox/{$transmission->filename}";

        Storage::put($outboxPath, $transmission->xml_content);

        Log::info('[SdiPecChannel] XML saved to PEC outbox.', [
            'transmission_id' => $transmission->id,
            'filename'        => $transmission->filename,
            'path'            => $outboxPath,
        ]);

        // In production, this would send via PEC SMTP:
        // Mail::to(config('sdi.pec_recipient'))
        //     ->send(new FatturaPecMailable($transmission));
    }
}
