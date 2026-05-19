<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function isStub(): bool
    {
        return config('services.midtrans.mode', env('MIDTRANS_MODE', 'stub')) === 'stub'
            || empty(env('MIDTRANS_SERVER_KEY'));
    }

    public function isProduction(): bool
    {
        return (bool) env('MIDTRANS_IS_PRODUCTION', false);
    }

    /**
     * Buat snap token untuk order. Stub mode return URL ke route stub-callback yang langsung mark paid.
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

        // Production mode — actual Midtrans Snap call would be here.
        // composer require midtrans/midtrans-php to use SDK.
        Log::warning('[Midtrans] Production mode not implemented yet');
        return ['mode' => 'error', 'message' => 'Midtrans production belum diimplementasikan'];
    }
}
