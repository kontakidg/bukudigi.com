<?php

namespace App\Console\Commands;

use App\Jobs\GenerateOgCardJob;
use App\Models\Book;
use Illuminate\Console\Command;

class OgCardBackfill extends Command
{
    protected $signature = 'og-card:backfill {--force : Regenerate untuk semua buku, termasuk yang sudah punya og_card_path}';

    protected $description = 'Generate OG card (1200x630) untuk semua buku active. Dispatch sync supaya progress kelihatan.';

    public function handle(): int
    {
        $query = Book::where('status', 'active');
        if (! $this->option('force')) {
            $query->whereNull('og_card_path');
        }

        $books = $query->get();
        $total = $books->count();

        if ($total === 0) {
            $this->info('Semua buku sudah punya og_card_path. Pakai --force untuk regenerate.');
            return self::SUCCESS;
        }

        $this->info("Generating OG card untuk {$total} buku...");
        $bar = $this->output->createProgressBar($total);

        foreach ($books as $book) {
            try {
                GenerateOgCardJob::dispatchSync($book->id);
            } catch (\Throwable $e) {
                $this->error("\nFAILED {$book->slug}: ".$e->getMessage());
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Done.');
        return self::SUCCESS;
    }
}
