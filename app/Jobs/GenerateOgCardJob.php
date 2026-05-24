<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\OgCardGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateOgCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public int $bookId)
    {
    }

    public function handle(OgCardGenerator $service): void
    {
        $book = Book::find($this->bookId);
        if (! $book) {
            return;
        }

        try {
            $path = $service->generate($book);
            if ($path) {
                $book->update(['og_card_path' => $path]);
                Log::info('[GenerateOgCardJob] OG card created', [
                    'book' => $book->slug,
                    'path' => $path,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[GenerateOgCardJob] Failed', [
                'book' => $book->slug,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
