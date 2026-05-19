<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FonnteWhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class WhatsAppOtpController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->wa_verified_at) {
            return redirect()->route('profile.edit')->with('status', 'WhatsApp sudah terverifikasi.');
        }

        if (empty($user->phone)) {
            return redirect()->route('profile.edit')->withErrors([
                'phone' => 'Isi nomor WhatsApp dulu sebelum verifikasi.',
            ]);
        }

        return view('auth.wa-verify', [
            'phone' => $user->phone,
            'sent' => session('wa_sent', false),
        ]);
    }

    public function send(Request $request, FonnteWhatsApp $wa): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->phone, 422, 'Phone belum diisi');

        $key = 'wa-otp-send:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['phone' => "Tunggu {$seconds} detik sebelum minta OTP lagi."]);
        }
        RateLimiter::hit($key, 300);

        $otp = $wa->sendOtp($user->phone);

        $msg = $wa->isStub()
            ? "OTP terkirim ke {$user->phone} (mode STUB — cek storage/logs/laravel.log). Kode: {$otp}"
            : "OTP terkirim ke {$user->phone}. Cek WhatsApp kamu.";

        return redirect()->route('wa.verify.show')->with('status', $msg)->with('wa_sent', true);
    }

    public function verify(Request $request, FonnteWhatsApp $wa): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'string', 'size:6']]);

        $user = $request->user();
        abort_unless($user->phone, 422);

        if (! $wa->verifyOtp($user->phone, $request->input('otp'))) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah expired.']);
        }

        $user->update(['wa_verified_at' => now()]);

        return redirect()->route('profile.edit')->with('status', 'WhatsApp berhasil diverifikasi.');
    }
}
