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
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]+$/'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'bank_name' => ['required', 'string', 'max:64'],
            'bank_account' => ['required', 'string', 'max:64'],
            'bank_holder' => ['required', 'string', 'max:120'],
            'ktp_image' => ['required', 'image', 'max:5120'],
            'selfie_image' => ['required', 'image', 'max:5120'],
            'agree_tos' => ['accepted'],
        ], [
            'agree_tos.accepted' => 'Kamu harus menyetujui syarat author.',
        ]);

        $ktpPath = $request->file('ktp_image')->store('author-docs/'.$user->id, 'local');
        $selfiePath = $request->file('selfie_image')->store('author-docs/'.$user->id, 'local');

        Author::create([
            'user_id' => $user->id,
            'display_name' => $data['display_name'],
            'bio' => $data['bio'] ?? null,
            'nik' => $data['nik'],
            'npwp' => $data['npwp'] ?? null,
            'bank_name' => $data['bank_name'],
            'bank_account' => $data['bank_account'],
            'bank_holder' => $data['bank_holder'],
            'ktp_image_path' => $ktpPath,
            'selfie_image_path' => $selfiePath,
            'status' => 'pending',
        ]);

        $user->update(['role' => 'author']);

        return redirect()->route('author.dashboard')
            ->with('status', 'Pendaftaran author berhasil! Admin akan review dalam 1-2 hari kerja.');
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

        return view('author.dashboard', compact('author', 'books', 'stats'));
    }
}
