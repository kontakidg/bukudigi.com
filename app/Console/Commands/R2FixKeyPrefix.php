<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Fix orphan R2 objects yang disimpan dengan absolute path prefix
 * (bug versi awal config disk 'private' yang inherit 'root' dari local).
 *
 * Cara pakai:
 *   php artisan r2:fix-key-prefix --dry-run   # preview
 *   php artisan r2:fix-key-prefix             # beneran rename
 */
class R2FixKeyPrefix extends Command
{
    protected $signature = 'r2:fix-key-prefix
                            {--dry-run : Preview saja tanpa copy/delete}
                            {--prefix=/www/wwwroot/bukudigi.com/storage/app/private/ : Prefix absolute path yang harus distrip}';

    protected $description = 'Recover file di R2 yang disimpan dengan absolute path prefix (bug konfig lama).';

    public function handle(): int
    {
        $disk = Storage::disk('private');
        $prefix = $this->option('prefix');
        $prefix = ltrim($prefix, '/'); // R2 key tidak pakai leading slash
        $dryRun = (bool) $this->option('dry-run');

        $this->info('=== R2 Key Prefix Recovery ===');
        $this->line("Prefix: {$prefix}");
        $this->line('Mode: '.($dryRun ? 'DRY-RUN' : 'COPY + DELETE old'));
        $this->newLine();

        // Daftar relative path yang mau di-recover dari book + order
        $candidates = [];

        foreach (\App\Models\Book::whereNotNull('pdf_master_path')->get() as $b) {
            $candidates[] = $b->pdf_master_path;
        }
        foreach (\App\Models\Book::whereNotNull('epub_master_path')->get() as $b) {
            $candidates[] = $b->epub_master_path;
        }
        foreach (\App\Models\Order::whereNotNull('watermarked_pdf_path')->get() as $o) {
            $candidates[] = $o->watermarked_pdf_path;
        }
        foreach (\App\Models\Order::whereNotNull('watermarked_epub_path')->get() as $o) {
            $candidates[] = $o->watermarked_epub_path;
        }

        $candidates = array_unique($candidates);
        $this->line('Candidates from DB: '.count($candidates));
        $this->newLine();

        $recovered = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($candidates as $rel) {
            $oldKey = $prefix . $rel;
            $newKey = $rel;

            // Cek di key absolute ada
            $content = null;
            try {
                $content = $disk->get($oldKey);
            } catch (\Throwable $e) {
                // ignore
            }

            if ($content === null) {
                $this->line("[skip] {$rel} — not found at absolute key");
                $notFound++;
                continue;
            }

            $size = strlen($content);
            $this->line("[found] {$rel} — {$size} bytes at {$oldKey}");

            if ($dryRun) {
                $recovered++;
                continue;
            }

            try {
                $disk->put($newKey, $content);
                $disk->delete($oldKey);
                $this->info("   → copied to {$newKey} + deleted old");
                $recovered++;
            } catch (\Throwable $e) {
                $this->error('   → FAIL: '.$e->getMessage());
                $skipped++;
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Recovered: {$recovered}");
        $this->line("Not found at abs key: {$notFound}");
        $this->line("Failed copy:  {$skipped}");

        return self::SUCCESS;
    }
}
