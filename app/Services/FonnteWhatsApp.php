<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteWhatsApp
{
    public function isStub(): bool
    {
        return config('services.fonnte.mode') === 'stub'
            || empty(config('services.fonnte.token'));
    }

    public function send(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);

        if ($this->isStub()) {
            Log::info('[Fonnte STUB] WA send', ['to' => $phone, 'message' => $message]);
            return ['status' => 'stub', 'to' => $phone];
        }

        $response = Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->asForm()->post(config('services.fonnte.url'), [
            'target' => $phone,
            'message' => $message,
        ]);

        if (! $response->ok()) {
            Log::warning('[Fonnte] WA send failed', ['to' => $phone, 'response' => $response->body()]);
            return ['status' => 'error', 'error' => $response->body()];
        }

        return ['status' => 'sent'] + $response->json();
    }

    public function sendOtp(string $phone): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $phone = $this->normalizePhone($phone);

        Cache::put($this->otpCacheKey($phone), $otp, now()->addMinutes(10));

        $this->send($phone, "Kode OTP bukudigi.com: *{$otp}*\n\nBerlaku 10 menit. Jangan dibagikan ke siapa pun.");

        return $otp;
    }

    public function verifyOtp(string $phone, string $otp): bool
    {
        $phone = $this->normalizePhone($phone);
        $expected = Cache::get($this->otpCacheKey($phone));

        if ($expected && hash_equals($expected, trim($otp))) {
            Cache::forget($this->otpCacheKey($phone));
            return true;
        }

        return false;
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }

    private function otpCacheKey(string $phone): string
    {
        return "wa-otp:{$phone}";
    }
}
