<?php

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PdfToImageService
{
    private string $binary;

    public function __construct()
    {
        $this->binary = $this->detectBinary();
    }

    public function isAvailable(): bool
    {
        return $this->binary !== '';
    }

    /**
     * Render N halaman pertama dari PDF jadi PNG.
     *
     * @param  string  $pdfAbs    Absolute path PDF input
     * @param  string  $outDirAbs Output directory (akan dibuat kalau belum ada)
     * @param  string  $prefix    Prefix filename, e.g. "preview" → preview-1.png, preview-2.png
     * @param  int     $maxPages
     * @param  int     $dpi       Resolution
     * @return string[]           Daftar absolute path PNG yang dihasilkan
     * @throws \RuntimeException
     */
    public function render(string $pdfAbs, string $outDirAbs, string $prefix = 'preview', int $maxPages = 5, int $dpi = 120): array
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('Ghostscript binary tidak ditemukan. Set GHOSTSCRIPT_BIN di .env atau install gs.');
        }
        if (! is_file($pdfAbs)) {
            throw new \RuntimeException("PDF tidak ditemukan: {$pdfAbs}");
        }

        if (! is_dir($outDirAbs)) {
            mkdir($outDirAbs, 0775, true);
        }

        // Hapus existing PNG dengan prefix sama agar tidak ada sisa lama
        foreach (glob($outDirAbs . DIRECTORY_SEPARATOR . $prefix . '-*.png') ?: [] as $stale) {
            @unlink($stale);
        }

        $outPattern = $outDirAbs . DIRECTORY_SEPARATOR . $prefix . '-%d.png';

        $process = new Process([
            $this->binary,
            '-dNOPAUSE',
            '-dBATCH',
            '-dQUIET',
            '-sDEVICE=png16m',
            '-r' . $dpi,
            '-dFirstPage=1',
            '-dLastPage=' . $maxPages,
            '-sOutputFile=' . $outPattern,
            $pdfAbs,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $files = glob($outDirAbs . DIRECTORY_SEPARATOR . $prefix . '-*.png') ?: [];
        sort($files, SORT_NATURAL);
        return $files;
    }

    private function detectBinary(): string
    {
        // 1. Explicit config
        $configured = config('services.ghostscript.binary');
        if ($configured && is_file($configured)) {
            return $configured;
        }

        // 2. Standard paths
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [
                'C:\Program Files\gs\gs10.03.1\bin\gswin64c.exe',
                'C:\Program Files\gs\gs10.02.1\bin\gswin64c.exe',
                'C:\Program Files\gs\gs10.01.2\bin\gswin64c.exe',
                'C:\Program Files\gs\gs9.56.1\bin\gswin64c.exe',
            ]
            : ['/usr/bin/gs', '/usr/local/bin/gs'];

        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $c;
            }
        }

        // 3. Auto-detect latest Windows gs install
        if (PHP_OS_FAMILY === 'Windows') {
            $dirs = glob('C:\Program Files\gs\gs*\bin\gswin64c.exe') ?: [];
            if (! empty($dirs)) {
                sort($dirs);
                return end($dirs);
            }
        }

        // 4. Try `which gs` / PATH lookup
        $which = PHP_OS_FAMILY === 'Windows' ? 'where gswin64c' : 'which gs';
        $out = @shell_exec($which);
        if ($out) {
            $first = trim(explode("\n", $out)[0] ?? '');
            if ($first && is_file($first)) {
                return $first;
            }
        }

        return '';
    }
}
