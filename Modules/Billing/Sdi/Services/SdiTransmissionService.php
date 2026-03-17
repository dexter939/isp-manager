<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Sdi\Enums\SdiNotificationCode;
use Modules\Billing\Sdi\Enums\SdiStatus;
use Modules\Billing\Sdi\Events\SdiNotificationReceived;
use Modules\Billing\Sdi\Events\SdiTransmissionCreated;
use Modules\Billing\Sdi\Exceptions\SdiTransmissionException;
use Modules\Billing\Sdi\Models\SdiNotification;
use Modules\Billing\Sdi\Models\SdiTransmission;
use Throwable;

class SdiTransmissionService
{
    public function __construct(
        private readonly FatturaXmlGenerator $xmlGenerator,
        private readonly SdiArubaChannel     $arubaChannel,
        private readonly SdiPecChannel       $pecChannel,
    ) {}

    /**
     * Creates a transmission record, generates the FatturaPA XML, and sends
     * the invoice to the SDI via the specified channel.
     *
     * When config('app.carrier_mock') is true, delivery is simulated.
     *
     * @param  Invoice $invoice The invoice to transmit.
     * @param  string  $channel The channel to use: 'aruba' or 'pec'.
     * @return SdiTransmission  The created transmission record.
     *
     * @throws SdiTransmissionException If the channel send fails.
     */
    public function send(Invoice $invoice, string $channel = 'aruba'): SdiTransmission
    {
        $xmlContent = $this->xmlGenerator->generate($invoice);
        $filename   = $this->xmlGenerator->generateFilename($invoice);
        $xmlHash    = hash('sha256', $xmlContent);

        $transmission = DB::transaction(function () use ($invoice, $channel, $xmlContent, $filename, $xmlHash): SdiTransmission {
            $transmission = SdiTransmission::create([
                'invoice_id'  => $invoice->id,
                'channel'     => $channel,
                'status'      => SdiStatus::Pending->value,
                'filename'    => $filename,
                'xml_content' => $xmlContent,
                'xml_hash'    => $xmlHash,
            ]);

            SdiTransmissionCreated::dispatch($transmission);

            return $transmission;
        });

        try {
            $this->dispatchToChannel($transmission);

            $isMock = config('app.carrier_mock', false);

            DB::transaction(function () use ($transmission, $isMock): void {
                $transmission->refresh()->lockForUpdate();

                $now = now();

                $transmission->update([
                    'status'                   => $isMock ? SdiStatus::Delivered->value : SdiStatus::Sent->value,
                    'sent_at'                  => $now,
                    'conservazione_expires_at' => $now->copy()->addYears(config('sdi.retention_years', 10)),
                ]);
            });
        } catch (Throwable $e) {
            DB::transaction(function () use ($transmission, $e): void {
                $transmission->refresh()->lockForUpdate();
                $transmission->update([
                    'status'     => SdiStatus::Error->value,
                    'last_error' => $e->getMessage(),
                ]);
            });

            throw SdiTransmissionException::channelFailed($transmission->channel, $e->getMessage());
        }

        return $transmission->fresh();
    }

    /**
     * Transmits a collection of invoices in batch via the configured SDI channel.
     *
     * @param  Collection<Invoice> $invoices The invoices to transmit.
     * @return array{sent: int, errors: array<int, array{invoice_id: int, error: string}>}
     */
    public function sendBatch(Collection $invoices): array
    {
        $channel = config('sdi.channel', 'aruba');
        $sent    = 0;
        $errors  = [];

        foreach ($invoices as $invoice) {
            try {
                $this->send($invoice, $channel);
                $sent++;
            } catch (Throwable $e) {
                $errors[] = [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ];

                Log::error('[SdiTransmissionService] Batch send error.', [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'errors' => $errors];
    }

    /**
     * Processes an incoming SDI notification, updates the transmission status,
     * creates a SdiNotification record, and fires SdiNotificationReceived event.
     *
     * @param  string $notificationCode The SDI notification code (RC/MC/NS/EC/AT/DT/SF).
     * @param  string $transmissionId   The transmission ID (database primary key or UUID).
     * @param  string $rawPayload       The raw notification payload.
     * @return void
     */
    public function processNotification(
        string $notificationCode,
        string $transmissionId,
        string $rawPayload,
    ): void {
        $code = SdiNotificationCode::from($notificationCode);

        DB::transaction(function () use ($code, $transmissionId, $rawPayload): void {
            $transmission = SdiTransmission::where('id', $transmissionId)
                ->orWhere('uuid', $transmissionId)
                ->lockForUpdate()
                ->firstOrFail();

            $notification = SdiNotification::create([
                'transmission_id'   => $transmission->id,
                'notification_type' => $code->value,
                'received_at'       => now(),
                'raw_payload'       => $rawPayload,
                'processed'         => true,
            ]);

            $newStatus = $code->toStatus();

            $transmission->update([
                'status'            => $newStatus->value,
                'notification_code' => $code->value,
            ]);

            SdiNotificationReceived::dispatch($notification);
        });
    }

    /**
     * Retries sending a failed or stuck transmission.
     *
     * Increments retry_count, then re-sends if count < config('sdi.max_retries', 3).
     * Throws if the transmission is terminal or max retries exceeded.
     *
     * @param  SdiTransmission $transmission The transmission to retry.
     * @return void
     *
     * @throws SdiTransmissionException If the transmission cannot be retried.
     */
    public function retry(SdiTransmission $transmission): void
    {
        DB::transaction(function () use ($transmission): void {
            $transmission = SdiTransmission::where('id', $transmission->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transmission->isTerminal()) {
                throw SdiTransmissionException::alreadyTerminal((string) $transmission->id);
            }

            $maxRetries = (int) config('sdi.max_retries', 3);

            if ($transmission->retry_count >= $maxRetries) {
                throw SdiTransmissionException::maxRetriesExceeded((string) $transmission->id, $maxRetries);
            }

            $transmission->increment('retry_count');
        });

        $transmission->refresh();

        $invoice = $transmission->invoice;
        $xmlContent = $this->xmlGenerator->generate($invoice);
        $xmlHash    = hash('sha256', $xmlContent);

        DB::transaction(function () use ($transmission, $xmlContent, $xmlHash): void {
            $transmission->lockForUpdate();
            $transmission->update([
                'xml_content' => $xmlContent,
                'xml_hash'    => $xmlHash,
                'status'      => SdiStatus::Pending->value,
                'last_error'  => null,
            ]);
        });

        try {
            $this->dispatchToChannel($transmission);

            $isMock = config('app.carrier_mock', false);

            DB::transaction(function () use ($transmission, $isMock): void {
                $transmission->refresh()->lockForUpdate();
                $now = now();
                $transmission->update([
                    'status'  => $isMock ? SdiStatus::Delivered->value : SdiStatus::Sent->value,
                    'sent_at' => $now,
                    'conservazione_expires_at' => $now->copy()->addYears(config('sdi.retention_years', 10)),
                ]);
            });
        } catch (Throwable $e) {
            DB::transaction(function () use ($transmission, $e): void {
                $transmission->refresh()->lockForUpdate();
                $transmission->update([
                    'status'     => SdiStatus::Error->value,
                    'last_error' => $e->getMessage(),
                ]);
            });

            throw SdiTransmissionException::channelFailed($transmission->channel, $e->getMessage());
        }
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function dispatchToChannel(SdiTransmission $transmission): void
    {
        match ($transmission->channel) {
            'aruba' => $this->arubaChannel->send($transmission),
            'pec'   => $this->pecChannel->send($transmission),
            default => throw SdiTransmissionException::channelFailed($transmission->channel, 'Unknown channel'),
        };
    }
}
