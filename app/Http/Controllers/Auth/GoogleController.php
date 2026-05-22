<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google login belum dikonfigurasi. Hubungi admin atau pakai email/password.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google login belum dikonfigurasi.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage()]);
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal login dengan Google: '.$e->getMessage(),
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $isNewUser = false;
        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'User',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);
            $isNewUser = true;
        } elseif (! $user->google_id) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        // Welcome mail untuk user baru (Google signup)
        if ($isNewUser) {
            try {
                Mail::to($user->email)->queue(new WelcomeMail($user));
            } catch (Throwable $e) {
                Log::warning('WelcomeMail (Google signup) queue failed: '.$e->getMessage());
            }
        }

        Auth::login($user, remember: true);

        $default = $user->isAdmin() ? '/admin' : route('library');
        return redirect()->intended($default);
    }

    private function isConfigured(): bool
    {
        return ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'));
    }
}
