<?php

namespace App\Console\Commands;

use App\Jobs\WatermarkEpubJob;
use App\Models\Order;
use App\Services\EpubWatermarkService;
use App\Support\PrivateStorage;
use Illuminate\Console\Command;
use ZipArchive;

class EpubDiagnose extends Command
{
    protected $signature = 'epub:diagnose {order_code}
                            {--rewatermark : Re-run watermark job dari master EPUB}
                            {--recover : Re-apply watermark service ke watermarked file langsung (kalau master hilang)}';

    protected $description = 'Inspect EPUB master + watermarked untuk order tertentu, debug masalah reader.';

    public function handle(): int
    {
        $code = $this->argument('order_code');
        $order = Order::with('book', 'user')->where('order_code', $code)->first();
        if (! $order) {
            $this->error("Order {$code} tidak ditemukan.");
            return self::FAILURE;
        }

        $this->info("=== Order {$code} ===");
        $this->line("Buyer:  {$order->user->name} <{$order->user->email}>");
        $this->line("Book:   {$order->book->title}");

        $masterRel = $order->book->epub_master_path;
        $wmRel = $order->watermarked_epub_path;
        $this->line("Master EPUB:     {$masterRel}");
        $this->line("Watermarked:     ".($wmRel ?? '(null)'));
        $this->line('');

        if (! $masterRel) {
            $this->warn('Book ini tidak punya EPUB master. Author belum upload.');
            return self::SUCCESS;
        }

        $this->info('--- MASTER EPUB ---');
        $this->inspectByRel($masterRel);

        if ($wmRel) {
            $this->info('--- WATERMARKED EPUB ---');
            $this->inspectByRel($wmRel);
        }

        if ($this->option('recover')) {
            if (! $wmRel) {
                $this->error('Tidak ada watermarked file untuk recover.');
                return self::FAILURE;
            }
            $this->info('');
            $this->info('Recovering: re-apply watermark service ke watermarked file (idempotent — strip old marker + inject new clean watermark)...');
            try {
                // Download watermarked ke temp, apply service in-place, upload back
                $local = PrivateStorage::localPath($wmRel);
                app(EpubWatermarkService::class)->apply($local['path'], $local['path'], [
                    'name' => $order->user->name ?? 'Anonim',
                    'email' => $order->user->email ?? '-',
                    'order_code' => $order->order_code,
                ]);
                // Upload kembali kalau remote
                if (! empty($local['is_temp'])) {
                    PrivateStorage::putFromLocal($local['path'], $wmRel);
                }
                PrivateStorage::cleanup($local);
                $this->info('Done. Re-inspecting...');
                $this->inspectByRel($wmRel);
            } catch (\Throwable $e) {
                $this->error('Recovery failed: '.$e->getMessage());
                return self::FAILURE;
            }
            return self::SUCCESS;
        }

        if ($this->option('rewatermark')) {
            $this->info('');
            $this->info('Running WatermarkEpubJob...');
            $order->update(['watermarked_epub_path' => null]);
            WatermarkEpubJob::dispatchSync($order->id);
            $order->refresh();
            $this->line("Result: watermarked_epub_path = ".($order->watermarked_epub_path ?? 'null'));
            if ($order->watermarked_epub_path) {
                $this->info('--- WATERMARKED EPUB (baru) ---');
                $this->inspectByRel($order->watermarked_epub_path);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Inspect EPUB by relative path di private disk.
     * Download ke temp local kalau remote, lalu inspect.
     */
    private function inspectByRel(string $relPath): void
    {
        if (! PrivateStorage::exists($relPath)) {
            $this->error("  File NOT FOUND in private storage: {$relPath}");
            return;
        }

        $local = PrivateStorage::localPath($relPath);
        $this->inspectEpub($local['path']);
        PrivateStorage::cleanup($local);
    }

    private function inspectEpub(string $abs): void
    {
        if (! is_file($abs)) {
            $this->error("  File NOT FOUND: {$abs}");
            return;
        }

        $size = filesize($abs);
        $this->line("  Size:  ".number_format($size).' bytes ('.round($size / 1024, 1).' KB)');

        $zip = new ZipArchive();
        $result = $zip->open($abs, ZipArchive::CHECKCONS);
        if ($result !== true) {
            $this->error("  Zip open failed (code {$result}) — file CORRUPT or not a zip");
            return;
        }

        $this->line("  Zip entries: {$zip->numFiles}");

        $mimetype = $zip->getFromName('mimetype');
        $this->line('  mimetype:    '.($mimetype === false ? 'MISSING ❌' : (trim($mimetype) === 'application/epub+zip' ? 'OK ✓' : 'WRONG ('.$mimetype.')')));

        $container = $zip->getFromName('META-INF/container.xml');
        $this->line('  container:   '.($container === false ? 'MISSING ❌' : 'OK ✓ ('.strlen($container).' bytes)'));

        $opfPath = null;
        if ($container && preg_match('#full-path="([^"]+\.opf)"#u', $container, $m)) {
            $opfPath = $m[1];
        }
        if ($opfPath) {
            $opf = $zip->getFromName($opfPath);
            if ($opf === false) {
                $this->error("  content.opf: MISSING at {$opfPath} ❌");
            } else {
                $this->line("  content.opf: OK at {$opfPath} (".strlen($opf).' bytes)');
                $prev = libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $loaded = $dom->loadXML($opf);
                $errors = libxml_get_errors();
                libxml_clear_errors();
                libxml_use_internal_errors($prev);
                if (! $loaded) {
                    $this->error('  content.opf XML: INVALID');
                    foreach (array_slice($errors, 0, 3) as $err) {
                        $this->error('    - '.trim($err->message).' (line '.$err->line.')');
                    }
                } else {
                    $this->line('  content.opf XML: valid ✓');
                    $hasBuyer = str_contains($opf, 'bukudigi-buyer');
                    $this->line('  watermark marker: '.($hasBuyer ? 'present ✓' : 'absent'));

                    $items = $dom->getElementsByTagName('item');
                    $opfDir = trim(dirname($opfPath), '.');
                    $missing = [];
                    $count = 0;
                    foreach ($items as $item) {
                        $href = $item->getAttribute('href');
                        if (! $href) continue;
                        $count++;
                        $full = $opfDir ? "{$opfDir}/{$href}" : $href;
                        if ($zip->locateName($full) === false && $zip->locateName($href) === false) {
                            $missing[] = $href;
                        }
                    }
                    if (empty($missing)) {
                        $this->line("  manifest:    {$count} items, all present ✓");
                    } else {
                        $this->error('  manifest:    '.count($missing).' MISSING items (dari '.$count.'):');
                        foreach (array_slice($missing, 0, 5) as $m) {
                            $this->error("    - {$m}");
                        }
                    }
                }
            }
        }

        $zip->close();
    }
}
