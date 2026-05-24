<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Helper untuk akses disk 'private'.
 *
 * Banyak service kita (FPDI, ZipArchive, Ghostscript) butuh path local di filesystem.
 * Kalau disk private = local, langsung pakai ->path(). Kalau = R2 (remote), download
 * dulu ke temp file → return path → caller wajib panggil `cleanup($tempPath)` setelah selesai.
 */
class PrivateStorage
{
    /**
     * Pakai disk 'private' (alias yang config-driven).
     */
    public static function disk()
    {
        return Storage::disk('private');
    }

    /**
     * Cek apakah disk private = local (path() jalan langsung).
     */
    public static function isLocal(): bool
    {
        return config('filesystems.disks.private.driver') === 'local';
    }

    /**
     * Dapatkan absolute path local untuk file di disk private.
     * Kalau remote (R2), download ke temp file dulu.
     *
     * Return: absolute path local + optional temp flag.
     * Caller wajib panggil cleanup($result) kalau is_temp = true.
     */
    public static function localPath(string $relPath): array
    {
        if (self::isLocal()) {
            return [
                'path' => self::disk()->path($relPath),
                'is_temp' => false,
            ];
        }

        // Remote — download ke temp
        $tempPath = tempnam(sys_get_temp_dir(), 'bukudigi-');
        if ($tempPath === false) {
            throw new \RuntimeException('Failed to create temp file.');
        }

        $stream = self::disk()->readStream($relPath);
        if ($stream === false || $stream === null) {
            @unlink($tempPath);
            throw new \RuntimeException("File not found in private storage: {$relPath}");
        }

        $out = fopen($tempPath, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return [
            'path' => $tempPath,
            'is_temp' => true,
        ];
    }

    /**
     * Cleanup temp file (kalau is_temp = true).
     */
    public static function cleanup(array $result): void
    {
        if (! empty($result['is_temp']) && ! empty($result['path']) && is_file($result['path'])) {
            @unlink($result['path']);
        }
    }

    /**
     * Upload file lokal ke disk private + cleanup local copy (kalau remote).
     */
    public static function putFromLocal(string $localPath, string $destRel): void
    {
        if (! is_file($localPath)) {
            throw new \RuntimeException("Local file not found: {$localPath}");
        }

        $stream = fopen($localPath, 'rb');
        self::disk()->writeStream($destRel, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Size dari file di private storage (works for both local and remote).
     */
    public static function size(string $relPath): int
    {
        return (int) self::disk()->size($relPath);
    }
}
