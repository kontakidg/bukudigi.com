<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateEarning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    /**
     * Landing publik /affiliate — kasih info program & CTA daftar.
     */
    public function landing(): View
    {
        return view('affiliate.landing');
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->affiliate) {
            return redirect()->route('affiliate.dashboard');
        }
        return view('affiliate.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->affiliate, 422, 'Akun sudah terdaftar sebagai affiliate.');

        $data = $request->validate([
            'promo_channels' => ['required', 'string', 'max:500'],
            'motivation' => ['required', 'string', 'min:30', 'max:1500'],
            'commitment_agreed' => ['accepted'],
            'agree_tos' => ['accepted'],
        ], [
            'promo_channels.required' => 'Sebutkan minimal 1 channel promosi (IG, TikTok, blog, dll).',
            'motivation.required' => 'Ceritakan motivasi kamu jadi affiliate.',
            'motivation.min' => 'Motivasi minimal 30 karakter.',
            'commitment_agreed.accepted' => 'Kamu harus menyetujui komitmen affiliate.',
            'agree_tos.accepted' => 'Kamu harus menyetujui syarat program affiliate.',
        ]);

        Affiliate::create([
            'user_id' => $user->id,
            'code' => Affiliate::generateUniqueCode($user->name),
            'status' => 'pending',
            'promo_channels' => $data['promo_channels'],
            'motivation' => $data['motivation'],
            'commitment_agreed' => true,
            'commission_rate' => 10.00,
        ]);

        return redirect()->route('affiliate.dashboard')
            ->with('status', 'Pendaftaran affiliate berhasil! Admin akan review dalam 1–2 hari kerja. Kamu akan dapat notifikasi WhatsApp/email saat disetujui.');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->affiliate) {
            return redirect()->route('affiliate.register.show');
        }

        $affiliate = $user->affiliate;

        $stats = [
            'clicks_30d' => AffiliateClick::where('affiliate_id', $affiliate->id)
                ->where('clicked_at', '>=', now()->subDays(30))->count(),
            'clicks_total' => $affiliate->clicks_count,
            'conversions_total' => $affiliate->conversions_count,
            'conversion_rate' => $affiliate->clicks_count > 0
                ? round($affiliate->conversions_count / $affiliate->clicks_count * 100, 2)
                : 0,
        ];

        $earnings = AffiliateEarning::with('order.book', 'order.user')
            ->where('affiliate_id', $affiliate->id)
            ->latest()
            ->take(50)
            ->get();

        $payouts = $affiliate->payouts()->latest()->take(10)->get();

        return view('affiliate.dashboard', compact('affiliate', 'stats', 'earnings', 'payouts'));
    }
}
