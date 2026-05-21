<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function landing(): View
    {
        return view('public.jual');
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->author) {
            return redirect()->route('author.dashboard');
        }

        return view('author.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->author, 422, 'Akun sudah terdaftar sebagai author.');

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'nik' => ['nullable', 'string', 'size:16', 'regex:/^[0-9]+$/'],
            'npwp' => ['nullable', 'string', 'max:32'],
            // Bank & KYC opsional — bisa diisi nanti di profile saat mau payout
            'bank_name' => ['nullable', 'string', 'max:64'],
            'bank_account' => ['nullable', 'string', 'max:64'],
            'bank_holder' => ['nullable', 'string', 'max:120'],
            'ktp_image' => ['nullable', 'image', 'max:5120'],
            'selfie_image' => ['nullable', 'image', 'max:5120'],
            'agree_tos' => ['accepted'],
        ], [
            'agree_tos.accepted' => 'Kamu harus menyetujui syarat author.',
        ]);

        $ktpPath = null;
        $selfiePath = null;
        if ($request->hasFile('ktp_image')) {
            $ktpPath = $request->file('ktp_image')->store('author-docs/'.$user->id, 'local');
        }
        if ($request->hasFile('selfie_image')) {
            $selfiePath = $request->file('selfie_image')->store('author-docs/'.$user->id, 'local');
        }

        Author::create([
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

        return view('author.dashboard', compact('author', 'books', 'stats', 'showBankReminder', 'needsBankInfo'));
    }
}
