<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Throwable;

class MidtransService
{
    public function __construct()
    {
        if (! $this->isStub()) {
            Config::$serverKey = config('services.midtrans.server_key');
            Config::$isProduction = (bool) config('services.midtrans.is_production', false);
            Config::$isSanitized = true;
            Config::$is3ds = (bool) config('services.midtrans.enable_3ds', true);
        }
    }

    public function isStub(): bool
    {
        return config('services.midtrans.mode') === 'stub'
            || empty(config('services.midtrans.server_key'));
    }

    public function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production', false);
    }

    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    /**
     * Snap.js script URL — beda untuk sandbox vs production.
     */
    public function snapJsUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * Buat snap token untuk order. Stub mode return URL ke route stub-callback.
     *
     * Return: ['mode' => 'stub'|'live'|'error', 'token' => ..., 'redirect_url' => ..., 'message' => ...]
     */
    public function createSnapToken(Order $order): array
    {
        if ($this->isStub()) {
            Log::info('[Midtrans STUB] Create snap token', ['order' => $order->order_code]);
            return [
                'mode' => 'stub',
                'redirect_url' => route('checkout.stub.pay', $order->order_code),
                'token' => 'stub-'.$order->order_code,
            ];
        }

        $payable = $order->net_amount ?: $order->gross_amount;

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => (int) $payable,
            ],
            'item_details' => [[
                'id' => 'BOOK-'.$order->book_id,
                'price' => (int) $payable,
                'quantity' => 1,
                'name' => mb_substr($order->book?->title ?? 'Ebook', 0, 50),
                'category' => $order->book?->category?->name ?? 'Ebook',
            ]],
            'customer_details' => [
                'first_name' => mb_substr($order->user?->name ?? 'Customer', 0, 50),
                'email' => $order->user?->email,
                'phone' => $order->user?->phone,
            ],
            'callbacks' => array_filter([
                'finish' => config('services.midtrans.finish_url') ?: route('library'),
            ]),
            // Disable mode credit card raw — pakai Snap default
            'credit_card' => ['secure' => true],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return [
                'mode' => 'live',
                'token' => $snapToken,
                'client_key' => $this->clientKey(),
                'snap_js_url' => $this->snapJsUrl(),
            ];
        } catch (Throwable $e) {
            Log::error('[Midtrans] createSnapToken failed', [
                'order' => $order->order_code,
                'error' => $e->getMessage(),
            ]);
            return [
                'mode' => 'error',
                'message' => 'Gagal generate payment token: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Parse + verify notification dari webhook Midtrans.
     * Throws kalau signature invalid atau request body invalid.
     *
     * Return:
     *   [
     *     'order_id'           => string (= order_code di sistem kita),
     *     'transaction_status' => 'capture'|'settlement'|'pending'|'deny'|'cancel'|'expire'|'failure',
     *     'fraud_status'       => 'accept'|'challenge'|'deny',
     *     'payment_type'       => string,
     *     'gross_amount'       => string,
     *     'raw'                => stdClass (full notification object),
     *   ]
     */
    public function parseNotification(): array
    {
        $notif = new Notification();

        // verifySignatureKey throws kalau invalid (sejak Midtrans-php v2.6+ verifikasi otomatis).
        // Tapi defensive — check sendiri juga.
        $expected = hash(
            'sha512',
            $notif->order_id.$notif->status_code.$notif->gross_amount.config('services.midtrans.server_key')
        );

        if (! hash_equals($expected, (string) $notif->signature_key)) {
            throw new \RuntimeException('Invalid Midtrans signature');
        }

        return [
            'order_id' => (string) $notif->order_id,
            'transaction_status' => (string) $notif->transaction_status,
            'fraud_status' => (string) ($notif->fraud_status ?? ''),
            'payment_type' => (string) $notif->payment_type,
            'gross_amount' => (string) $notif->gross_amount,
            'transaction_id' => (string) ($notif->transaction_id ?? ''),
            'raw' => $notif,
        ];
    }

    /**
     * Helper: tentukan status order final berdasarkan notification.
     * Map Midtrans status → Order::status.
     */
    public function mapTransactionStatus(string $transactionStatus, string $fraudStatus = ''): string
    {
        return match (true) {
            // capture + accept (credit card) ATAU settlement = paid
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'paid',
            $transactionStatus === 'settlement' => 'paid',
            // pending masih nunggu user bayar di VA/QRIS
            $transactionStatus === 'pending' => 'pending',
            // deny, cancel, expire, failure → fail
            in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true) => 'failed',
            default => 'pending',
        };
    }
}
