<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Brick\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Billing\Models\PrepaidTopupProduct;

class PayPalPaymentService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('prepaid.paypal_mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Create a PayPal order for a top-up product.
     * Returns PayPal order_id.
     */
    public function createOrder(PrepaidTopupProduct $product): string
    {
        if (config('carrier_mock', false)) {
            return 'MOCK-ORDER-' . Str::random(10);
        }

        $accessToken = $this->getAccessToken();

        $amount = Money::ofMinor($product->amount_amount, $product->amount_currency);

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent'         => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $product->amount_currency,
                            'value'         => $amount->getAmount()->__toString(),
                        ],
                        'description' => $product->name,
                    ],
                ],
            ]);

        $response->throw();

        return $response->json('id');
    }

    /**
     * Capture a PayPal order.
     */
    public function captureOrder(string $orderId): bool
    {
        if (config('carrier_mock', false)) {
            return true;
        }

        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            Log::error('PayPal captureOrder failed', [
                'order_id' => $orderId,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            return false;
        }

        $status = $response->json('status');
        return $status === 'COMPLETED';
    }

    /**
     * Refund a PayPal capture.
     */
    public function refundCapture(string $captureId, Money $amount): bool
    {
        if (config('carrier_mock', false)) {
            return true;
        }

        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/payments/captures/{$captureId}/refund", [
                'amount' => [
                    'value'         => $amount->getAmount()->__toString(),
                    'currency_code' => $amount->getCurrency()->getCurrencyCode(),
                ],
            ]);

        if ($response->failed()) {
            Log::error('PayPal refundCapture failed', [
                'capture_id' => $captureId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Obtain a PayPal OAuth2 access token (cached in Redis for 1 hour).
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'paypal_access_token';

        return Cache::remember($cacheKey, 3600, function (): string {
            $clientId     = config('prepaid.paypal_client_id');
            $clientSecret = config('prepaid.paypal_client_secret');

            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            $response->throw();

            return $response->json('access_token');
        });
    }
}
