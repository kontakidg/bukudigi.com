<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * PayPal REST API v2 — Smart Payment Buttons (server-side create + capture).
 *
 * Flow:
 *   1. Frontend Smart Button createOrder callback → backend createOrder() → PayPal API → return paypal_order_id
 *   2. User approve di PayPal popup
 *   3. Frontend onApprove callback → backend captureOrder() → PayPal API → mark order paid
 *
 * Currency: USD (PayPal native). Harga IDR auto-convert pakai env PAYPAL_USD_TO_IDR.
 */
class PaypalService
{
    public function isActive(): bool
    {
        return config('services.payment_gateway') === 'paypal'
            && ! empty(config('services.paypal.client_id'))
            && ! empty(config('services.paypal.client_secret'));
    }

    public function isProduction(): bool
    {
        return config('services.paypal.mode') === 'live';
    }

    public function clientId(): ?string
    {
        return config('services.paypal.client_id');
    }

    public function currency(): string
    {
        return config('services.paypal.currency', 'USD');
    }

    public function apiBase(): string
    {
        return $this->isProduction()
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * SDK script URL untuk frontend (Smart Buttons).
     */
    public function sdkScriptUrl(): string
    {
        return 'https://www.paypal.com/sdk/js?'.http_build_query([
            'client-id' => $this->clientId(),
            'currency' => $this->currency(),
            'intent' => 'capture',
        ]);
    }

    /**
     * Ambil kurs USD/IDR real-time dari open.er-api.com.
     * Di-cache 6 jam. Fallback ke .env PAYPAL_USD_TO_IDR kalau API gagal.
     *
     * Return array:
     *   rate        float   — berapa IDR per 1 USD
     *   source      string  — 'live' | 'fallback'
     *   updated_at  string  — waktu update (human readable WIB)
     *   age_minutes int     — usia cache dalam menit
     */
    public function liveRate(): array
    {
        $fallback = (float) config('services.paypal.usd_to_idr', 16000);
        $cacheKey = 'paypal_usd_idr_rate';

        $cached = Cache::get($cacheKey);
        if ($cached) {
            $ageMinutes = (int) round((time() - $cached['fetched_at']) / 60);
            return [
                'rate'        => $cached['rate'],
                'source'      => 'live',
                'updated_at'  => \Carbon\Carbon::createFromTimestamp($cached['fetched_at'])
                                    ->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                'age_minutes' => $ageMinutes,
            ];
        }

        // Fetch dari open.er-api.com (free, no key)
        try {
            $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD');

            if ($response->successful()) {
                $idrRate = (float) ($response->json('rates.IDR') ?? 0);

                if ($idrRate > 1000) {   // sanity check: IDR pasti > 1000 per USD
                    $data = ['rate' => $idrRate, 'fetched_at' => time()];
                    Cache::put($cacheKey, $data, now()->addHours(6));

                    return [
                        'rate'        => $idrRate,
                        'source'      => 'live',
                        'updated_at'  => now()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                        'age_minutes' => 0,
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('[PayPal] Fetch live rate failed, using fallback', ['error' => $e->getMessage()]);
        }

        // Fallback ke .env
        return [
            'rate'        => $fallback,
            'source'      => 'fallback',
            'updated_at'  => 'dari konfigurasi',
            'age_minutes' => 0,
        ];
    }

    /**
     * Konversi IDR → USD (2 desimal) pakai kurs live.
     */
    public function convertIdrToUsd(int $idr): float
    {
        $rate = $this->liveRate()['rate'];
        if ($rate <= 0) {
            throw new RuntimeException('Kurs USD ke IDR tidak tersedia.');
        }
        return round($idr / $rate, 2);
    }

    /**
     * Get OAuth access token (cached 8 jam — PayPal token valid 9 jam).
     */
    public function accessToken(): string
    {
        $cacheKey = 'paypal_access_token:'.($this->isProduction() ? 'live' : 'sandbox');
        return Cache::remember($cacheKey, now()->addHours(8), function () {
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.paypal.client_id'),
                    config('services.paypal.client_secret')
                )
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->apiBase().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('PayPal OAuth failed: '.$response->status().' '.$response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Bikin order di PayPal — dipanggil saat Smart Button createOrder callback.
     * Return PayPal order ID untuk di-passing ke frontend.
     */
    public function createOrder(Order $order): array
    {
        $payable  = $order->net_amount ?: $order->gross_amount;
        $rateInfo = $this->liveRate();
        $usd      = round((int) $payable / $rateInfo['rate'], 2);

        if ($usd < 0.5) {
            return ['error' => 'Nominal terlalu kecil ($'.$usd.'). PayPal minimum $0.50.'];
        }

        try {
            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_code,
                    'description' => mb_substr('Ebook: '.$order->book?->title, 0, 127),
                    'custom_id' => $order->order_code,
                    'amount' => [
                        'currency_code' => $this->currency(),
                        'value' => (string) number_format($usd, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'bukudigi.com',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                ],
            ];

            Log::debug('[PayPal] createOrder request payload', ['payload' => $payload, 'usd' => $usd]);

            $response = Http::withToken($this->accessToken())
                ->asJson()
                ->post($this->apiBase().'/v2/checkout/orders', $payload);

            if (! $response->successful()) {
                $body = $response->json();
                Log::warning('[PayPal] createOrder failed', [
                    'order' => $order->order_code,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                // Extract detail PayPal v2 error structure (name + message + details[].issue)
                $name = $body['name'] ?? null;
                $msg = $body['message'] ?? null;
                $issue = $body['details'][0]['issue'] ?? null;
                $description = $body['details'][0]['description'] ?? null;

                $errorParts = array_filter([$name, $issue, $msg, $description]);
                $errorText = $errorParts ? implode(' — ', $errorParts) : ('HTTP '.$response->status());

                return ['error' => 'PayPal: '.$errorText];
            }

            $paypalOrderId = $response->json('id');

            // Simpan paypal_order_id + USD amount ke DB
            $order->update([
                'payment_gateway' => 'paypal',
                'paypal_order_id' => $paypalOrderId,
                'paypal_amount_usd' => $usd,
                'paypal_usd_rate' => $rateInfo['rate'],
            ]);

            return [
                'id' => $paypalOrderId,
                'amount_usd' => $usd,
            ];
        } catch (Throwable $e) {
            Log::error('[PayPal] createOrder exception', [
                'order' => $order->order_code,
                'error' => $e->getMessage(),
            ]);
            return ['error' => 'PayPal error: '.$e->getMessage()];
        }
    }

    /**
     * Capture order — dipanggil saat user approve.
     * Return ['status' => 'COMPLETED'|'PENDING'|'FAILED', 'capture_id' => ..., 'raw' => ...]
     */
    public function captureOrder(string $paypalOrderId): array
    {
        try {
            $response = Http::withToken($this->accessToken())
                ->withBody('{}', 'application/json')
                ->withHeaders(['Prefer' => 'return=representation'])
                ->post($this->apiBase().'/v2/checkout/orders/'.$paypalOrderId.'/capture');

            if (! $response->successful()) {
                Log::warning('[PayPal] captureOrder failed', [
                    'paypal_order_id' => $paypalOrderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'status' => 'FAILED',
                    'error' => $response->json('message', 'capture failed'),
                ];
            }

            $data = $response->json();
            $status = $data['status'] ?? 'UNKNOWN';
            $captureId = $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

            return [
                'status' => $status,
                'capture_id' => $captureId,
                'raw' => $data,
            ];
        } catch (Throwable $e) {
            Log::error('[PayPal] captureOrder exception', [
                'paypal_order_id' => $paypalOrderId,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'FAILED', 'error' => $e->getMessage()];
        }
    }
}
