<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

/**
 * Migrate all existing files dari storage/app/private (local) ke disk 'private' (R2).
 *
 * Cara pakai:
 *   1. Pastikan .env sudah set R2 credentials + PRIVATE_DISK_DRIVER=s3
 *   2. php artisan files:migrate-to-r2 --dry-run   # preview saja
 *   3. php artisan files:migrate-to-r2             # beneran upload
 *   4. (optional) php artisan files:migrate-to-r2 --cleanup-local  # hapus local setelah verify
 */
class FilesMigrateToR2 extends Command
{
    protected $signature = 'files:migrate-to-r2
                            {--dry-run : Show what would be uploaded tanpa beneran upload}
                            {--cleanup-local : Setelah upload sukses, hapus file local}';

    protected $description = 'Migrate file dari storage/app/private (local) ke disk private (R2). Jalankan setelah set credentials R2 di .env.';

    public function handle(): int
    {
        $localRoot = storage_path('app/private');
        if (! is_dir($localRoot)) {
            $this->error("Local private dir not found: {$localRoot}");
            return self::FAILURE;
        }

        $driver = config('filesystems.disks.private.driver');
        if ($driver !== 's3') {
            $this->error("PRIVATE_DISK_DRIVER masih 'local'. Set ke 's3' di .env dulu sebelum migrasi.");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $cleanup = (bool) $this->option('cleanup-local');

        $this->info('=== Migrate local files → R2 ===');
        $this->line('Source: '.$localRoot);
        $this->line('Target: disk private (R2 bucket '.config('filesystems.disks.private.bucket').')');
        $this->line('Mode:   '.($dryRun ? 'DRY-RUN (preview saja)' : ($cleanup ? 'UPLOAD + CLEANUP local' : 'UPLOAD (keep local)')));
        $this->newLine();

        $finder = (new Finder())->files()->in($localRoot)->ignoreDotFiles(true);

        $total = 0;
        $uploaded = 0;
        $skipped = 0;
        $failed = 0;
        $totalBytes = 0;

        $disk = Storage::disk('private');

        foreach ($finder as $file) {
            $rel = ltrim(str_replace('\\', '/', $file->getRelativePathname()), '/');
            $size = $file->getSize();
            $total++;
            $totalBytes += $size;

            // Skip .gitignore files
            if (str_ends_with($rel, '.gitignore')) {
                $skipped++;
                continue;
            }

            $sizeKb = round($size / 1024, 1);
            $this->line(sprintf('  %s (%s KB)', $rel, $sizeKb));

            if ($dryRun) {
                continue;
            }

            try {
                // Cek sudah ada di R2 dengan size sama → skip
                if ($disk->exists($rel) && (int) $disk->size($rel) === (int) $size) {
                    $this->line("    → already exists with same size, skip");
                    $skipped++;
                    continue;
                }

                $stream = fopen($file->getRealPath(), 'rb');
                $disk->writeStream($rel, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $uploaded++;
                $this->line('    → uploaded');

                if ($cleanup) {
                    @unlink($file->getRealPath());
                    $this->line('    → local deleted');
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error('    → FAILED: '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Total files:    {$total}");
        $this->line('Total bytes:    '.number_format($totalBytes).' ('.round($totalBytes / 1024 / 1024, 1).' MB)');
        $this->line("Uploaded:       {$uploaded}");
        $this->line("Skipped:        {$skipped}");
        $this->line("Failed:         {$failed}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry-run: tidak ada file yang di-upload. Jalankan tanpa --dry-run untuk beneran migrate.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
