<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePreviewJob;
use App\Models\Book;
use Illuminate\Console\Command;

class RegenerateBookPreviews extends Command
{
    protected $signature = 'books:regenerate-previews {--missing-only : Hanya generate kalau preview_pdf_path NULL}';
    protected $description = 'Regenerate preview PDF (5 halaman) untuk semua buku active';

    public function handle(): int
    {
        $query = Book::where('status', 'active');
        if ($this->option('missing-only')) {
            $query->whereNull('preview_pdf_path');
        }

        $books = $query->get();
        $this->info("Processing {$books->count()} buku…");

        foreach ($books as $book) {
            GeneratePreviewJob::dispatchSync($book->id);
            $this->line("  ✓ {$book->title}");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
