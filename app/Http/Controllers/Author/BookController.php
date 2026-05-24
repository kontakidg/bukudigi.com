<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePreviewJob;
use App\Mail\BookSubmittedMail;
use App\Models\Book;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use setasign\Fpdi\Tcpdf\Fpdi;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class BookController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $author = $this->ensureVerifiedAuthor($request);
        if ($author instanceof RedirectResponse) {
            return $author;
        }

        $books = $author->books()->with('category')->latest()->paginate(15);

        return view('author.books.index', compact('books'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $author = $this->ensureVerifiedAuthor($request);
        if ($author instanceof RedirectResponse) {
            return $author;
        }

        // Author harus punya minimal 1 pen name sebelum upload buku
        $penNames = $author->penNames()->orderByDesc('is_default')->orderBy('name')->get();
        if ($penNames->isEmpty()) {
            return redirect()->route('author.pen-names.create')
                ->with('status', 'Buat pen name dulu sebelum upload buku.');
        }

        return view('author.books.create', [
            'categories' => Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get(),
            'tagOptions' => Tag::orderBy('name')->pluck('name')->all(),
            'penNames' => $penNames,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $author = $this->ensureVerifiedAuthor($request);
        if ($author instanceof RedirectResponse) {
            return $author;
        }

        $data = $this->validateBookForm($request, isCreate: true);

        // Validate pen_name_id milik author ini
        $penNameId = $this->resolvePenNameId($author, $data['pen_name_id'] ?? null);

        $slug = $this->makeSlug($data['title']);

        $coverPath = $this->storeCover($request->file('cover'), $author->id, $slug);
        $pdfPath = $this->storePdf($request->file('pdf'), $slug);
        $this->validateUploadedPdf($pdfPath);
        $pdfSize = \App\Support\PrivateStorage::size($pdfPath);

        $epubPath = null;
        if ($request->hasFile('epub')) {
            $epubPath = $this->storeEpub($request->file('epub'), $slug);
            $this->validateUploadedEpub($epubPath);
        }

        $book = DB::transaction(function () use ($author, $data, $slug, $coverPath, $pdfPath, $pdfSize, $epubPath, $penNameId) {
            $book = Book::create([
                'author_id' => $author->id,
                'pen_name_id' => $penNameId,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'],
                'table_of_contents' => $data['table_of_contents'] ?? null,
                'cover_path' => $coverPath,
                'pdf_master_path' => $pdfPath,
                'epub_master_path' => $epubPath,
                'price' => $data['price'],
                'page_count' => $data['page_count'] ?? null,
                'file_size_bytes' => $pdfSize,
                'status' => 'pending_review',
                'ai_disclosure' => (bool) ($data['ai_disclosure'] ?? false),
                'ai_platforms_used' => $this->parseAiPlatforms($data['ai_platforms'] ?? null),
                'submitted_at' => now(),
            ]);

            if (! empty($data['tags'])) {
                $this->syncTags($book, $data['tags']);
            }

            return $book;
        });

        GeneratePreviewJob::dispatch($book->id);

        // Email konfirmasi submit
        try {
            Mail::to($author->user->email)->queue(new BookSubmittedMail($book));
        } catch (\Throwable $e) {
            Log::warning('BookSubmittedMail queue failed: '.$e->getMessage());
        }

        return redirect()->route('author.books.index')
            ->with('status', "Buku \"{$book->title}\" berhasil dikirim untuk moderasi. Admin akan review dalam 1-2 hari kerja.");
    }

    public function edit(Request $request, Book $book): View|RedirectResponse
    {
        $author = $this->ensureVerifiedAuthor($request);
        if ($author instanceof RedirectResponse) {
            return $author;
        }

        $this->ensureOwnedAndEditable($book, $author->id);

        return view('author.books.edit', [
            'book' => $book->load('tags'),
            'categories' => Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get(),
            'tagOptions' => Tag::orderBy('name')->pluck('name')->all(),
            'penNames' => $author->penNames()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $author = $this->ensureVerifiedAuthor($request);
        if ($author instanceof RedirectResponse) {
            return $author;
        }

        $this->ensureOwnedAndEditable($book, $author->id);

        $data = $this->validateBookForm($request, isCreate: false);

        $penNameId = $this->resolvePenNameId($author, $data['pen_name_id'] ?? null);

        $updates = [
            'pen_name_id' => $penNameId,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'table_of_contents' => $data['table_of_contents'] ?? null,
            'price' => $data['price'],
            'page_count' => $data['page_count'] ?? null,
            'ai_disclosure' => (bool) ($data['ai_disclosure'] ?? false),
            'ai_platforms_used' => $this->parseAiPlatforms($data['ai_platforms'] ?? null),
        ];

        if ($request->hasFile('cover')) {
            $updates['cover_path'] = $this->storeCover($request->file('cover'), $author->id, $book->slug);
        }

        if ($request->hasFile('pdf')) {
            $updates['pdf_master_path'] = $this->storePdf($request->file('pdf'), $book->slug);
            $this->validateUploadedPdf($updates['pdf_master_path']);
            $updates['file_size_bytes'] = \App\Support\PrivateStorage::size($updates['pdf_master_path']);
        }

        if ($request->hasFile('epub')) {
            $updates['epub_master_path'] = $this->storeEpub($request->file('epub'), $book->slug);
            $this->validateUploadedEpub($updates['epub_master_path']);
        }

        DB::transaction(function () use ($book, $updates, $data) {
            $book->update($updates);
            if (isset($data['tags'])) {
                $this->syncTags($book, $data['tags']);
            }
        });

        // Regenerate preview kalau PDF diganti
        if (isset($updates['pdf_master_path'])) {
            GeneratePreviewJob::dispatch($book->id);
        }

        return redirect()->route('author.books.index')
            ->with('status', "Buku \"{$book->title}\" tersimpan.");
    }

    public function submit(Request $request, Book $book): RedirectResponse
    {
        $author = $this->ensureVerifiedAuthor($request);
        if ($author instanceof RedirectResponse) {
            return $author;
        }

        abort_unless($book->author_id === $author->id, 403);

        if (! in_array($book->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => 'Buku tidak dalam status yang bisa di-submit ulang.']);
        }

        $book->update([
            'status' => 'pending_review',
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()->route('author.books.index')
            ->with('status', "Buku \"{$book->title}\" dikirim ulang untuk moderasi.");
    }

    public function archive(Request $request, Book $book): RedirectResponse
    {
        $author = $this->ensureVerifiedAuthor($request);
        if ($author instanceof RedirectResponse) {
            return $author;
        }

        abort_unless($book->author_id === $author->id, 403);

        if ($book->status === 'archived') {
            return back()->withErrors(['status' => 'Buku sudah di-archive.']);
        }

        $book->update(['status' => 'archived']);

        return redirect()->route('author.books.index')
            ->with('status', "Buku \"{$book->title}\" di-archive. Pembeli existing tetap bisa download.");
    }

    // === helpers ===

    private function ensureVerifiedAuthor(Request $request): \App\Models\Author|RedirectResponse
    {
        $user = $request->user();
        if (! $user->author) {
            return redirect()->route('author.register.show')
                ->with('status', 'Daftar sebagai penulis dulu untuk upload buku.');
        }
        if ($user->author->status !== 'verified') {
            return redirect()->route('author.dashboard')
                ->withErrors(['status' => 'Akun penulis kamu belum diverifikasi admin.']);
        }
        return $user->author;
    }

    private function ensureOwnedAndEditable(Book $book, int $authorId): void
    {
        abort_unless($book->author_id === $authorId, 403);
        abort_unless(in_array($book->status, ['draft', 'rejected', 'active']), 403,
            'Buku dengan status ini tidak bisa diedit.');
    }

    private function validateBookForm(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'table_of_contents' => ['nullable', 'string', 'max:3000'],
            'category_id' => ['required', 'exists:categories,id'],
            'pen_name_id' => ['nullable', 'integer', 'exists:pen_names,id'],
            'price' => ['required', 'integer', 'min:15000', 'max:500000'],
            'page_count' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'cover' => [$isCreate ? 'required' : 'nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'pdf' => [$isCreate ? 'required' : 'nullable', 'file', 'max:51200', 'mimes:pdf'],
            'epub' => ['nullable', 'file', 'max:51200', 'mimes:epub,zip'],
            'ai_disclosure' => ['nullable', 'boolean'],
            'ai_platforms' => ['nullable', 'string', 'max:255'],
            'agree_terms' => ['accepted'],
        ], [
            'price.min' => 'Harga minimum Rp 15.000.',
            'price.max' => 'Harga maksimum Rp 500.000.',
            'pdf.max' => 'PDF maksimal 50 MB.',
            'epub.max' => 'EPUB maksimal 50 MB.',
            'epub.mimes' => 'File harus berformat .epub.',
            'cover.max' => 'Cover maksimal 5 MB.',
            'agree_terms.accepted' => 'Kamu harus mengkonfirmasi bahwa konten asli/berhak dijual.',
        ]);
    }

    /**
     * Validate pen_name_id milik author; fallback ke default pen name kalau null/invalid.
     */
    private function resolvePenNameId(\App\Models\Author $author, ?int $requestedId): ?int
    {
        if ($requestedId) {
            $belongs = $author->penNames()->where('id', $requestedId)->exists();
            if ($belongs) {
                return $requestedId;
            }
        }
        // Fallback: default pen name
        $default = $author->defaultPenName ?? $author->penNames()->first();
        return $default?->id;
    }

    private function makeSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base . '-' . Str::lower(Str::random(6));
        // Ensure unique (collision extremely unlikely tapi tetap defensive)
        while (Book::where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::lower(Str::random(6));
        }
        return $slug;
    }

    private function storeCover(UploadedFile $file, int $authorId, string $slug): string
    {
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $name = "{$slug}.{$ext}";
        return $file->storeAs("book-covers/{$authorId}", $name, 'public');
    }

    private function storePdf(UploadedFile $file, string $slug): string
    {
        return $file->storeAs("books/{$slug}", 'master.pdf', 'private');
    }

    private function storeEpub(UploadedFile $file, string $slug): string
    {
        return $file->storeAs("books/{$slug}", 'master.epub', 'private');
    }

    /**
     * Pastikan EPUB beneran archive zip dengan content.opf (struktur EPUB valid).
     * Kalau invalid, hapus + throw validation error.
     */
    private function validateUploadedEpub(string $relPath): void
    {
        $local = \App\Support\PrivateStorage::localPath($relPath);
        $zip = new \ZipArchive();
        if ($zip->open($local['path']) !== true) {
            \App\Support\PrivateStorage::cleanup($local);
            \App\Support\PrivateStorage::disk()->delete($relPath);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'epub' => 'File EPUB rusak atau bukan archive zip valid.',
            ]);
        }
        $hasContainer = $zip->locateName('META-INF/container.xml') !== false;
        $zip->close();
        \App\Support\PrivateStorage::cleanup($local);

        if (! $hasContainer) {
            \App\Support\PrivateStorage::disk()->delete($relPath);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'epub' => 'File EPUB tidak valid (META-INF/container.xml tidak ditemukan).',
            ]);
        }
    }

    /**
     * Pastikan PDF yang baru di-upload bisa di-parse oleh FPDI.
     * Kalau tidak, hapus file dan throw validation error agar author retry.
     */
    private function validateUploadedPdf(string $relPath): void
    {
        $local = \App\Support\PrivateStorage::localPath($relPath);
        try {
            $pdf = new Fpdi();
            $count = $pdf->setSourceFile($local['path']);
            if ($count < 1) {
                throw new \RuntimeException('PDF tidak punya halaman.');
            }
            \App\Support\PrivateStorage::cleanup($local);
        } catch (Throwable $e) {
            \App\Support\PrivateStorage::cleanup($local);
            \App\Support\PrivateStorage::disk()->delete($relPath);
            throw ValidationException::withMessages([
                'pdf' => 'File PDF tidak valid atau ter-encrypt. Pastikan PDF normal (bisa dibuka di Acrobat/Chrome) dan tanpa password. Error: '.$e->getMessage(),
            ]);
        }
    }

    private function parseAiPlatforms(?string $raw): ?array
    {
        if (! $raw) return null;
        $items = array_filter(array_map('trim', explode(',', $raw)));
        return $items ? array_values($items) : null;
    }

    private function syncTags(Book $book, ?string $tagsRaw): void
    {
        if (! $tagsRaw) {
            $book->tags()->sync([]);
            return;
        }

        $names = array_filter(array_unique(array_map('trim', explode(',', $tagsRaw))));
        $ids = [];
        foreach ($names as $name) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $ids[] = $tag->id;
        }
        $book->tags()->sync($ids);
    }
}
