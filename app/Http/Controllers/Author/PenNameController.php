<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\PenName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PenNameController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $author = $this->ensureAuthor($request);
        if ($author instanceof RedirectResponse) return $author;

        $penNames = $author->penNames()->withCount('books')->orderByDesc('is_default')->orderBy('name')->get();

        return view('author.pen-names.index', compact('penNames'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $author = $this->ensureAuthor($request);
        if ($author instanceof RedirectResponse) return $author;

        return view('author.pen-names.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $author = $this->ensureAuthor($request);
        if ($author instanceof RedirectResponse) return $author;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // Pastikan name unik dalam author (case-insensitive)
        $exists = $author->penNames()
            ->whereRaw('LOWER(name) = ?', [Str::lower($data['name'])])
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Kamu sudah punya pen name dengan nama ini.'])->withInput();
        }

        $author->penNames()->create([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        return redirect()->route('author.pen-names.index')
            ->with('status', "Pen name \"{$data['name']}\" berhasil dibuat.");
    }

    public function edit(Request $request, PenName $penName): View|RedirectResponse
    {
        $author = $this->ensureAuthor($request);
        if ($author instanceof RedirectResponse) return $author;
        abort_unless($penName->author_id === $author->id, 403);

        return view('author.pen-names.edit', compact('penName'));
    }

    public function update(Request $request, PenName $penName): RedirectResponse
    {
        $author = $this->ensureAuthor($request);
        if ($author instanceof RedirectResponse) return $author;
        abort_unless($penName->author_id === $author->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $exists = $author->penNames()
            ->where('id', '!=', $penName->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($data['name'])])
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Kamu sudah punya pen name dengan nama ini.'])->withInput();
        }

        $penName->update([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);
        // slug auto-update kalau name berubah? Skip — pertahankan slug demi link permanen

        return redirect()->route('author.pen-names.index')
            ->with('status', "Pen name \"{$penName->name}\" tersimpan.");
    }

    public function destroy(Request $request, PenName $penName): RedirectResponse
    {
        $author = $this->ensureAuthor($request);
        if ($author instanceof RedirectResponse) return $author;
        abort_unless($penName->author_id === $author->id, 403);

        // Cek punya buku tidak
        $bookCount = $penName->books()->count();
        if ($bookCount > 0) {
            return back()->withErrors([
                'delete' => "Pen name \"{$penName->name}\" punya {$bookCount} buku. Pindahkan ke pen name lain dulu sebelum hapus.",
            ]);
        }

        // Cek jangan hapus pen name terakhir
        if ($author->penNames()->count() <= 1) {
            return back()->withErrors([
                'delete' => 'Tidak bisa hapus pen name terakhir — author harus punya minimal 1 pen name.',
            ]);
        }

        $wasDefault = $penName->is_default;
        $penName->delete();

        // Kalau yang dihapus adalah default, set pen name lain jadi default
        if ($wasDefault) {
            $newDefault = $author->penNames()->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return redirect()->route('author.pen-names.index')
            ->with('status', 'Pen name dihapus.');
    }

    private function ensureAuthor(Request $request): \App\Models\Author|RedirectResponse
    {
        $user = $request->user();
        if (! $user->author) {
            return redirect()->route('author.register.show')
                ->with('status', 'Daftar sebagai penulis dulu.');
        }
        return $user->author;
    }
}
