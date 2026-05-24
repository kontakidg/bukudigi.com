<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

/**
 * Scan SEMUA object di R2 bucket, deteksi key yang punya prefix bermasalah
 * (absolute path / double-bucket), lalu copy ke key relative + delete yang lama.
 *
 * Pattern yang di-fix:
 *   - bukudigi-private//www/wwwroot/.../storage/app/private/X  → X
 *   - /www/wwwroot/.../storage/app/private/X                   → X
 *   - www/wwwroot/.../storage/app/private/X                    → X
 */
class R2FixKeyPrefix extends Command
{
    protected $signature = 'r2:fix-key-prefix
                            {--dry-run : Preview tanpa copy/delete}';

    protected $description = 'Recover R2 objects yang disimpan dengan prefix bermasalah (absolute path / double-bucket bug).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $bucket = config('filesystems.disks.private.bucket');

        $this->info('=== R2 Key Recovery ===');
        $this->line("Bucket: {$bucket}");
        $this->line('Mode: '.($dryRun ? 'DRY-RUN' : 'COPY + DELETE old'));
        $this->newLine();

        $client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => config('filesystems.disks.private.endpoint'),
            'credentials' => [
                'key' => config('filesystems.disks.private.key'),
                'secret' => config('filesystems.disks.private.secret'),
            ],
            'use_path_style_endpoint' => true,
        ]);

        // Pattern marker untuk strip prefix
        $marker = 'storage/app/private/';

        $continuationToken = null;
        $total = 0;
        $fixed = 0;
        $skipped = 0;
        $failed = 0;

        do {
            $params = ['Bucket' => $bucket, 'MaxKeys' => 1000];
            if ($continuationToken) $params['ContinuationToken'] = $continuationToken;
            $resp = $client->listObjectsV2($params);

            foreach (($resp['Contents'] ?? []) as $obj) {
                $key = $obj['Key'];
                $size = $obj['Size'];
                $total++;

                // Cari posisi 'storage/app/private/' di dalam key
                $pos = strpos($key, $marker);
                if ($pos === false) {
                    // Key sudah bersih — skip
                    $skipped++;
                    continue;
                }

                // Extract suffix setelah 'storage/app/private/'
                $newKey = substr($key, $pos + strlen($marker));
                if (empty($newKey) || $newKey === $key) {
                    $skipped++;
                    continue;
                }

                $this->line(sprintf('[fix] %s → %s (%s KB)', $key, $newKey, round($size / 1024, 1)));

                if ($dryRun) {
                    $fixed++;
                    continue;
                }

                try {
                    // Copy ke key baru
                    $client->copyObject([
                        'Bucket' => $bucket,
                        'Key' => $newKey,
                        'CopySource' => $bucket . '/' . rawurlencode($key),
                    ]);
                    // Delete key lama
                    $client->deleteObject(['Bucket' => $bucket, 'Key' => $key]);
                    $fixed++;
                } catch (\Throwable $e) {
                    $this->error('   → FAIL: '.$e->getMessage());
                    $failed++;
                }
            }

            $continuationToken = $resp['IsTruncated'] ?? false ? $resp['NextContinuationToken'] : null;
        } while ($continuationToken);

        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Total scanned: {$total}");
        $this->line("Fixed:         {$fixed}");
        $this->line("Skipped (OK):  {$skipped}");
        $this->line("Failed:        {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
