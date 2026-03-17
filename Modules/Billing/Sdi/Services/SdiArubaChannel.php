<?php

declare(strict_types=1);

namespace Modules\Billing\Sdi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Sdi\Exceptions\SdiTransmissionException;
use Modules\Billing\Sdi\Models\SdiTransmission;

class SdiArubaChannel
{
    private Client $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Sends a FatturaPA XML transmission to the Aruba SDI API endpoint.
     *
     * Uses HMAC-SHA256 authentication with the configured API key.
     * When config('app.carrier_mock') is true, the transmission is simulated.
     *
     * @param  SdiTransmission $transmission The transmission record to send.
     * @return void
     *
     * @throws SdiTransmissionException If the Aruba API returns an error.
     */
    public function send(SdiTransmission $transmission): void
    {
        if (config('app.carrier_mock', false)) {
            Log::info('[SdiArubaChannel] Mock mode — simulating delivery.', [
                'transmission_id' => $transmission->id,
                'filename'        => $transmission->filename,
            ]);

            return;
        }

        $endpoint  = rtrim((string) config('sdi.aruba_endpoint', 'https://fatturazioneelettronica.aruba.it/v1'), '/');
        $apiKey    = (string) config('sdi.aruba_api_key', '');
        $url       = "{$endpoint}/fatture/invio";
        $xmlContent = $transmission->xml_content;

        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $xmlContent . $timestamp, $apiKey);

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Content-Type'       => 'application/xml',
                    'X-Aruba-Timestamp'  => $timestamp,
                    'X-Aruba-Signature'  => $signature,
                    'X-Aruba-Filename'   => $transmission->filename,
                ],
                'body'    => $xmlContent,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw SdiTransmissionException::channelFailed(
                    'aruba',
                    "HTTP {$statusCode}: " . $response->getBody()->getContents()
                );
            }

            Log::info('[SdiArubaChannel] Transmission sent successfully.', [
                'transmission_id' => $transmission->id,
                'filename'        => $transmission->filename,
                'http_status'     => $statusCode,
            ]);
        } catch (GuzzleException $e) {
            throw SdiTransmissionException::channelFailed('aruba', $e->getMessage());
        }
    }
}
