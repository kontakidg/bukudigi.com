<?php

namespace App\Http\Controllers;

use App\Mail\AuthorRegisteredMail;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookView;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function landing(): View
    {
        return view('public.jual');
    }

    /**
     * Level verifikasi penulis dari Site Settings.
     * 1 = nama saja, 2 = nama + WA, 3 = nama + WA + NIK + KTP + selfie
     */
    private function verificationLevel(): int
    {
        return max(1, min(3, (int) Setting::get('author_verification_level', '1')));
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->author) {
            return redirect()->route('author.dashboard');
        }

        $level = $this->verificationLevel();

        // Level 2+: butuh phone di user account. Kalau belum diisi, redirect ke profile.
        if ($level >= 2 && empty($user->phone)) {
            return redirect()->route('profile.edit')
                ->with('status', 'Lengkapi nomor WhatsApp di profile dulu sebelum daftar penulis (Level '.$level.' butuh WA).');
        }

        return view('author.register', ['verificationLevel' => $level]);
    }

    public function register(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->author, 422, 'Akun sudah terdaftar sebagai author.');

        $level = $this->verificationLevel();

        // Level 2+: phone wajib ada di user
        if ($level >= 2 && empty($user->phone)) {
            return redirect()->route('profile.edit')
                ->withErrors(['phone' => 'Lengkapi nomor WhatsApp di profile dulu.']);
        }

        $rules = [
            'display_name' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'bank_name' => ['nullable', 'string', 'max:64'],
            'bank_account' => ['nullable', 'string', 'max:64'],
            'bank_holder' => ['nullable', 'string', 'max:120'],
            'agree_tos' => ['accepted'],
        ];

        if ($level >= 3) {
            $rules['nik'] = ['required', 'string', 'size:16', 'regex:/^[0-9]+$/'];
            $rules['ktp_image'] = ['required', 'image', 'max:5120'];
            $rules['selfie_image'] = ['required', 'image', 'max:5120'];
        } else {
            $rules['nik'] = ['nullable', 'string', 'size:16', 'regex:/^[0-9]+$/'];
            $rules['ktp_image'] = ['nullable', 'image', 'max:5120'];
            $rules['selfie_image'] = ['nullable', 'image', 'max:5120'];
        }

        $data = $request->validate($rules, [
            'agree_tos.accepted' => 'Kamu harus menyetujui syarat author.',
            'nik.required' => 'NIK wajib diisi (Level 3 verifikasi).',
            'ktp_image.required' => 'Foto KTP wajib diupload (Level 3 verifikasi).',
            'selfie_image.required' => 'Foto selfie + KTP wajib diupload (Level 3 verifikasi).',
        ]);

        $ktpPath = null;
        $selfiePath = null;
        if ($request->hasFile('ktp_image')) {
            $ktpPath = $request->file('ktp_image')->store('author-docs/'.$user->id, 'private');
        }
        if ($request->hasFile('selfie_image')) {
            $selfiePath = $request->file('selfie_image')->store('author-docs/'.$user->id, 'private');
        }

        $author = Author::create([
            'user_id' => $user->id,
            'display_name' => $data['display_name'],
            'bio' => $data['bio'] ?? null,
            'nik' => $data['nik'] ?? null,
            'npwp' => $data['npwp'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account' => $data['bank_account'] ?? null,
            'bank_holder' => $data['bank_holder'] ?? null,
            'ktp_image_path' => $ktpPath,
            'selfie_image_path' => $selfiePath,
            'status' => 'pending',
        ]);

        $user->update(['role' => 'author']);

        // Email notif "pendaftaran sedang di-review"
        try {
            Mail::to($user->email)->queue(new AuthorRegisteredMail($author));
        } catch (\Throwable $e) {
            Log::warning('AuthorRegisteredMail queue failed: '.$e->getMessage());
        }

        return redirect()->route('author.dashboard')
            ->with('status', 'Pendaftaran author berhasil! Admin akan review dalam 1-2 hari kerja.');
    }

    public function showBank(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->author) {
            return redirect()->route('author.register.show');
        }
        return view('author.bank', ['author' => $user->author]);
    }

    public function updateBank(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->author, 403);

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:64'],
            'bank_account' => ['required', 'string', 'max:64', 'regex:/^[0-9-]+$/'],
            'bank_holder' => ['required', 'string', 'max:120'],
            'npwp' => ['nullable', 'string', 'max:32'],
        ], [
            'bank_account.regex' => 'Nomor rekening hanya angka (boleh ada strip).',
        ]);

        $user->author->update($data);

        return redirect()->route('author.dashboard')
            ->with('status', 'Data rekening bank tersimpan. Payout royalti sekarang bisa diproses.');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->author) {
            return redirect()->route('author.register.show');
        }

        $author = $user->author;
        $books = $author->books()->latest()->take(10)->get();

        $stats = [
            'total_books' => $author->books()->count(),
            'active_books' => $author->books()->where('status', 'active')->count(),
            'pending_books' => $author->books()->where('status', 'pending_review')->count(),
            'total_sales' => $author->books()->sum('sales_count'),
        ];

        // Reminder: data bank belum lengkap & sudah ada saldo (atau ada penjualan)
        $needsBankInfo = empty($author->bank_account) || empty($author->bank_name);
        $hasIncome = ($author->balance_available > 0)
            || ($author->balance_pending > 0)
            || ($stats['total_sales'] > 0);
        $showBankReminder = $needsBankInfo && $hasIncome;

        // ===== Chart performance — views & sales 30 hari terakhir =====
        $allBooks = $author->books()->orderByDesc('sales_count')->orderByDesc('views_count')->get(['id', 'title', 'sales_count', 'views_count']);

        // Buku yang dipilih user (max 5). Default = top 5 by sales.
        $requestedIds = collect($request->input('books', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(5)
            ->values();

        $selectedBooks = $requestedIds->isNotEmpty()
            ? $allBooks->whereIn('id', $requestedIds->all())->values()
            : $allBooks->take(5)->values();

        // Range chart: 14|30|180|365|custom (default 30 hari)
        $range = (string) $request->input('range', '30');
        if (! in_array($range, ['14', '30', '180', '365', 'custom'], true)) {
            $range = '30';
        }

        $end = Carbon::today()->endOfDay();
        if ($range === 'custom') {
            $startInput = $request->input('start');
            $endInput = $request->input('end');
            try {
                $start = $startInput ? Carbon::parse($startInput)->startOfDay() : Carbon::today()->subDays(29)->startOfDay();
                $end = $endInput ? Carbon::parse($endInput)->endOfDay() : $end;
            } catch (\Throwable $e) {
                $start = Carbon::today()->subDays(29)->startOfDay();
            }
            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }
            // Safety: cap range max 2 tahun supaya query ga jebol
            if ($start->lt($end->copy()->subDays(730))) {
                $start = $end->copy()->subDays(730)->startOfDay();
            }
        } else {
            $days = (int) $range;
            $start = Carbon::today()->subDays($days - 1)->startOfDay();
        }

        $chart = $this->buildPerformanceChart($selectedBooks, $start, $end);
        $chartRange = [
            'preset' => $range,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];

        return view('author.dashboard', compact(
            'author', 'books', 'stats', 'showBankReminder', 'needsBankInfo',
            'allBooks', 'selectedBooks', 'chart', 'chartRange'
        ));
    }

    /**
     * Bangun data chart untuk rentang $start..$end.
     * Granularity otomatis:
     *   - rentang <= 45 hari:    per-hari    (daily)
     *   - rentang 46-200 hari:   per-minggu  (weekly)
     *   - rentang > 200 hari:    per-bulan   (monthly)
     */
    private function buildPerformanceChart($books, Carbon $start, Carbon $end): array
    {
        $startD = $start->copy()->startOfDay();
        $endD = $end->copy()->endOfDay();
        $days = (int) $startD->diffInDays($end->copy()->startOfDay()) + 1;

        $granularity = match (true) {
            $days <= 45  => 'daily',
            $days <= 200 => 'weekly',
            default      => 'monthly',
        };

        // Generate labels & keys sesuai granularity
        $labels = [];
        $dateKeys = [];
        if ($granularity === 'daily') {
            for ($i = 0; $i < $days; $i++) {
                $d = $startD->copy()->addDays($i);
                $labels[] = $d->format('j M');
                $dateKeys[] = $d->format('Y-m-d');
            }
        } elseif ($granularity === 'weekly') {
            // Iterasi minggu (Monday-Sunday). Pakai ISO week (%x-%v)
            $cursor = $startD->copy()->startOfWeek(); // Monday
            $lastWeek = $endD->copy()->startOfWeek();
            while ($cursor->lte($lastWeek)) {
                $labels[] = $cursor->format('j M');    // "11 May" (Senin awal minggu)
                $dateKeys[] = $cursor->format('o-W');  // ISO year-week, e.g. "2026-20"
                $cursor->addWeek();
            }
        } else {
            // Monthly: iterasi bulan
            $cursor = $startD->copy()->startOfMonth();
            $lastMonth = $endD->copy()->startOfMonth();
            while ($cursor->lte($lastMonth)) {
                $labels[] = $cursor->format('M y');   // "May 26"
                $dateKeys[] = $cursor->format('Y-m'); // "2026-05"
                $cursor->addMonth();
            }
        }

        if ($books->isEmpty()) {
            return [
                'labels' => $labels, 'views' => [], 'sales' => [],
                'days' => $days, 'granularity' => $granularity,
            ];
        }

        $bookIds = $books->pluck('id')->all();

        // SQL expression untuk group key sesuai granularity
        $viewKey = match ($granularity) {
            'daily'   => DB::raw('DATE(viewed_at) as d'),
            'weekly'  => DB::raw("DATE_FORMAT(viewed_at, '%x-%v') as d"),
            'monthly' => DB::raw("DATE_FORMAT(viewed_at, '%Y-%m') as d"),
        };
        $saleKey = match ($granularity) {
            'daily'   => DB::raw('DATE(paid_at) as d'),
            'weekly'  => DB::raw("DATE_FORMAT(paid_at, '%x-%v') as d"),
            'monthly' => DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as d"),
        };

        $viewRows = BookView::whereIn('book_id', $bookIds)
            ->whereBetween('viewed_at', [$startD, $endD])
            ->select('book_id', $viewKey, DB::raw('COUNT(*) as c'))
            ->groupBy('book_id', 'd')
            ->get()
            ->groupBy('book_id');

        $saleRows = Order::whereIn('book_id', $bookIds)
            ->whereIn('status', ['paid', 'watermarking', 'ready'])
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startD, $endD])
            ->select('book_id', $saleKey, DB::raw('COUNT(*) as c'))
            ->groupBy('book_id', 'd')
            ->get()
            ->groupBy('book_id');

        $views = [];
        $sales = [];
        foreach ($books as $book) {
            $viewMap = ($viewRows[$book->id] ?? collect())->keyBy('d');
            $saleMap = ($saleRows[$book->id] ?? collect())->keyBy('d');

            $vRow = [];
            $sRow = [];
            foreach ($dateKeys as $key) {
                $vRow[] = (int) ($viewMap[$key]->c ?? 0);
                $sRow[] = (int) ($saleMap[$key]->c ?? 0);
            }

            // Singkat judul untuk legenda chart (max 4 kata + …) — judul panjang bikin legend pecah
            $shortTitle = Str::words($book->title, 4, '');
            $shortTitle = rtrim($shortTitle, " \t\n\r\0\x0B—–-:,;.|/");
            if (mb_strlen($shortTitle) < mb_strlen($book->title)) {
                $shortTitle .= '…';
            }

            $views[] = ['label' => $shortTitle, 'data' => $vRow];
            $sales[] = ['label' => $shortTitle, 'data' => $sRow];
        }

        return [
            'labels' => $labels,
            'views' => $views,
            'sales' => $sales,
            'days' => $days,
            'granularity' => $granularity,
        ];
    }
}
