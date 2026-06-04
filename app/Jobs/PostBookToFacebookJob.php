<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\FacebookPageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostBookToFacebookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60; // retry setelah 60 detik

    public function __construct(public int $bookId) {}

    public function handle(FacebookPageService $fb): void
    {
        $book = Book::with('author', 'category')->find($this->bookId);

        if (! $book || $book->status !== 'active') {
            Log::info('[Facebook] Skip post — buku tidak aktif', ['book_id' => $this->bookId]);
            return;
        }

        $fb->postBook($book);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Facebook] PostBookToFacebookJob gagal', [
            'book_id' => $this->bookId,
            'error'   => $e->getMessage(),
        ]);
    }
}
