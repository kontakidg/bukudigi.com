<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateCode;
use App\Models\AffiliateEarning;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AffiliateController extends Controller
{
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

        DB::transaction(function () use ($user, $data) {
            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'promo_channels' => $data['promo_channels'],
                'motivation' => $data['motivation'],
                'commitment_agreed' => true,
                'commission_rate' => 10.00,
            ]);

            // Auto-create default code
            $affiliate->codes()->create([
                'code' => AffiliateCode::generateUniqueCode($user->name),
                'label' => 'Default',
                'is_default' => true,
            ]);
        });

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
        $affiliate->ensureDefaultCode(); // safety: pastikan ada minimal 1 code
        $codes = $affiliate->codes()->orderByDesc('is_default')->orderByDesc('created_at')->get();

        $stats = [
            'clicks_30d' => AffiliateClick::where('affiliate_id', $affiliate->id)
                ->where('clicked_at', '>=', now()->subDays(30))->count(),
            'clicks_total' => $affiliate->clicks_count,
            'conversions_total' => $affiliate->conversions_count,
            'conversion_rate' => $affiliate->clicks_count > 0
                ? round($affiliate->conversions_count / $affiliate->clicks_count * 100, 2)
                : 0,
        ];

        $earnings = AffiliateEarning::with('order.book', 'order.user', 'affiliateCode')
            ->where('affiliate_id', $affiliate->id)
            ->latest()
            ->take(50)
            ->get();

        $payouts = $affiliate->payouts()->latest()->take(10)->get();

        // Chart range: 14|30|180|365|custom
        $range = (string) $request->input('range', '30');
        if (! in_array($range, ['14', '30', '180', '365', 'custom'], true)) {
            $range = '30';
        }

        $end = Carbon::today()->endOfDay();
        if ($range === 'custom') {
            try {
                $start = $request->input('start') ? Carbon::parse($request->input('start'))->startOfDay() : Carbon::today()->subDays(29)->startOfDay();
                $end = $request->input('end') ? Carbon::parse($request->input('end'))->endOfDay() : $end;
            } catch (\Throwable $e) {
                $start = Carbon::today()->subDays(29)->startOfDay();
            }
            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }
            if ($start->lt($end->copy()->subDays(730))) {
                $start = $end->copy()->subDays(730)->startOfDay();
            }
        } else {
            $start = Carbon::today()->subDays((int) $range - 1)->startOfDay();
        }

        $chart = $this->buildChart($affiliate->id, $start, $end);
        $chartRange = [
            'preset' => $range,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];

        // List buku untuk link generator (top 60 by sales, hanya active)
        $linkableBooks = Book::where('status', 'active')
            ->orderByDesc('sales_count')
            ->orderByDesc('id')
            ->limit(60)
            ->get(['id', 'slug', 'title', 'price'])
            ->map(fn ($b) => [
                'slug' => $b->slug,
                'title' => $b->title,
                'price' => 'Rp '.number_format((int) $b->price, 0, ',', '.'),
                'url' => route('books.show', ['book' => $b->slug]),
            ])
            ->values();

        return view('affiliate.dashboard', compact(
            'affiliate', 'codes', 'stats', 'earnings', 'payouts', 'chart', 'chartRange', 'linkableBooks'
        ));
    }

    /**
     * Tambah code baru (max 10 per affiliate).
     */
    public function storeCode(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->affiliate, 403);

        $affiliate = $user->affiliate;

        if ($affiliate->codes()->count() >= AffiliateCode::MAX_PER_AFFILIATE) {
            return back()->withErrors(['code' => 'Maksimal '.AffiliateCode::MAX_PER_AFFILIATE.' kode per affiliate.']);
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:60'],
            'custom_code' => ['nullable', 'string', 'min:4', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
        ], [
            'custom_code.regex' => 'Kode hanya boleh huruf, angka, _ dan -.',
        ]);

        $code = ! empty($data['custom_code'])
            ? strtoupper($data['custom_code'])
            : AffiliateCode::generateUniqueCode($user->name);

        if (AffiliateCode::where('code', $code)->exists()) {
            return back()->withErrors(['custom_code' => 'Kode sudah dipakai. Coba lain.']);
        }

        $affiliate->codes()->create([
            'code' => $code,
            'label' => $data['label'] ?? null,
            'is_default' => false,
        ]);

        return back()->with('status', "Kode baru \"$code\" berhasil dibuat.");
    }

    /**
     * Hapus code (tidak boleh hapus default).
     */
    public function destroyCode(Request $request, AffiliateCode $code): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->affiliate && $code->affiliate_id === $user->affiliate->id, 403);

        if ($code->is_default) {
            return back()->withErrors(['code' => 'Kode default tidak bisa dihapus. Set kode lain jadi default dulu.']);
        }

        if ($code->conversions_count > 0) {
            return back()->withErrors(['code' => 'Kode ini sudah punya konversi. Tidak bisa dihapus.']);
        }

        $code->delete();
        return back()->with('status', 'Kode dihapus.');
    }

    /**
     * Set salah satu code jadi default.
     */
    public function setDefaultCode(Request $request, AffiliateCode $code): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->affiliate && $code->affiliate_id === $user->affiliate->id, 403);

        DB::transaction(function () use ($user, $code) {
            $user->affiliate->codes()->update(['is_default' => false]);
            $code->update(['is_default' => true]);
        });

        return back()->with('status', "Kode \"{$code->code}\" sekarang jadi default.");
    }

    /**
     * Bangun data chart klik & konversi per hari/minggu/bulan.
     */
    private function buildChart(int $affiliateId, Carbon $start, Carbon $end): array
    {
        $startD = $start->copy()->startOfDay();
        $endD = $end->copy()->endOfDay();
        $days = (int) $startD->diffInDays($end->copy()->startOfDay()) + 1;

        $granularity = match (true) {
            $days <= 45  => 'daily',
            $days <= 200 => 'weekly',
            default      => 'monthly',
        };

        $labels = [];
        $keys = [];
        if ($granularity === 'daily') {
            for ($i = 0; $i < $days; $i++) {
                $d = $startD->copy()->addDays($i);
                $labels[] = $d->format('j M');
                $keys[] = $d->format('Y-m-d');
            }
        } elseif ($granularity === 'weekly') {
            $cursor = $startD->copy()->startOfWeek();
            $last = $endD->copy()->startOfWeek();
            while ($cursor->lte($last)) {
                $labels[] = $cursor->format('j M');
                $keys[] = $cursor->format('o-W');
                $cursor->addWeek();
            }
        } else {
            $cursor = $startD->copy()->startOfMonth();
            $last = $endD->copy()->startOfMonth();
            while ($cursor->lte($last)) {
                $labels[] = $cursor->format('M y');
                $keys[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        }

        $clickExpr = match ($granularity) {
            'daily'   => DB::raw('DATE(clicked_at) as d, COUNT(*) as c'),
            'weekly'  => DB::raw("DATE_FORMAT(clicked_at, '%x-%v') as d, COUNT(*) as c"),
            'monthly' => DB::raw("DATE_FORMAT(clicked_at, '%Y-%m') as d, COUNT(*) as c"),
        };
        $convExpr = match ($granularity) {
            'daily'   => DB::raw('DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(amount),0) as s'),
            'weekly'  => DB::raw("DATE_FORMAT(created_at, '%x-%v') as d, COUNT(*) as c, COALESCE(SUM(amount),0) as s"),
            'monthly' => DB::raw("DATE_FORMAT(created_at, '%Y-%m') as d, COUNT(*) as c, COALESCE(SUM(amount),0) as s"),
        };

        $clickRows = AffiliateClick::where('affiliate_id', $affiliateId)
            ->whereBetween('clicked_at', [$startD, $endD])
            ->select($clickExpr)->groupBy('d')->get()->keyBy('d');

        $convRows = AffiliateEarning::where('affiliate_id', $affiliateId)
            ->whereIn('status', ['pending', 'available', 'paid'])
            ->whereBetween('created_at', [$startD, $endD])
            ->select($convExpr)->groupBy('d')->get()->keyBy('d');

        $clicks = [];
        $conversions = [];
        $earnings = [];
        foreach ($keys as $k) {
            $clicks[] = (int) ($clickRows[$k]->c ?? 0);
            $conversions[] = (int) ($convRows[$k]->c ?? 0);
            $earnings[] = (int) ($convRows[$k]->s ?? 0);
        }

        return [
            'labels' => $labels,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'earnings' => $earnings,
            'days' => $days,
            'granularity' => $granularity,
        ];
    }
}
