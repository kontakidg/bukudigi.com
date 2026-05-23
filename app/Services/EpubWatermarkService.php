<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

/**
 * Watermark EPUB via inject metadata tag dc:rights + dc:contributor
 * di content.opf (file XML utama struktur EPUB).
 *
 * Tidak modifikasi konten halaman — cuma metadata identitas pembeli.
 * Reader epub.js + kebanyakan reader desktop (Calibre, Kindle Previewer)
 * akan display info ini di bagian "About this book" / properties.
 */
class EpubWatermarkService
{
    /**
     * @param  string  $masterPath  Absolute path EPUB master (read-only)
     * @param  string  $destPath    Absolute path EPUB hasil watermark
     * @param  array   $buyer       ['name' => string, 'email' => string, 'order_code' => string]
     */
    public function apply(string $masterPath, string $destPath, array $buyer): void
    {
        if (! is_file($masterPath)) {
            throw new RuntimeException("EPUB master not found: {$masterPath}");
        }

        // 1) Copy master ke destinasi (EPUB = zip, kita modify in-place di copy)
        $destDir = dirname($destPath);
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        if (! @copy($masterPath, $destPath)) {
            throw new RuntimeException("Failed to copy EPUB to {$destPath}");
        }

        // 2) Open zip dan cari content.opf
        $zip = new ZipArchive();
        if ($zip->open($destPath) !== true) {
            throw new RuntimeException("Failed to open EPUB zip: {$destPath}");
        }

        $opfPath = $this->findOpfPath($zip);
        if (! $opfPath) {
            $zip->close();
            throw new RuntimeException('content.opf not found in EPUB (file mungkin bukan EPUB valid).');
        }

        $opfXml = $zip->getFromName($opfPath);
        if ($opfXml === false) {
            $zip->close();
            throw new RuntimeException("Failed to read {$opfPath}");
        }

        // 3) Inject metadata watermark
        $name = htmlspecialchars($buyer['name'] ?? 'Anonim', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($buyer['email'] ?? '-', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $orderCode = htmlspecialchars($buyer['order_code'] ?? '-', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $timestamp = date('Y-m-d H:i');

        $watermarkXml = <<<XML

    <!-- bukudigi.com watermark -->
    <dc:contributor opf:role="oth" id="bukudigi-buyer">{$name} &lt;{$email}&gt;</dc:contributor>
    <dc:rights>Dibeli oleh {$name} ({$email}) · Order {$orderCode} · {$timestamp} · via bukudigi.com</dc:rights>

XML;

        // Inject sebelum </metadata> closing tag. Replace pertama saja (kalau ada nested, lebih aman).
        $newOpf = preg_replace(
            '#</metadata>#u',
            $watermarkXml.'</metadata>',
            $opfXml,
            1,
            $count
        );

        if (! $count || $newOpf === null) {
            $zip->close();
            throw new RuntimeException('Failed to inject watermark — <metadata> tag missing in content.opf');
        }

        // 4) Replace content.opf di zip pakai flag overwrite (lebih aman dari deleteName)
        $zip->addFromString($opfPath, $newOpf);
        $zip->close();

        // 5) Verify file masih readable sebagai EPUB valid setelah modifikasi
        $verify = new ZipArchive();
        if ($verify->open($destPath, ZipArchive::CHECKCONS) !== true) {
            // Cleanup file rusak
            @unlink($destPath);
            throw new RuntimeException('EPUB corrupt setelah inject watermark. File mungkin awalnya sudah malformed.');
        }
        if ($verify->locateName('META-INF/container.xml') === false) {
            $verify->close();
            @unlink($destPath);
            throw new RuntimeException('container.xml hilang setelah watermark.');
        }
        $verify->close();
    }

    /**
     * Cari path content.opf via META-INF/container.xml (lokasi standar EPUB).
     */
    private function findOpfPath(ZipArchive $zip): ?string
    {
        $container = $zip->getFromName('META-INF/container.xml');
        if ($container === false) {
            return null;
        }

        if (preg_match('#full-path="([^"]+\.opf)"#u', $container, $m)) {
            return $m[1];
        }

        // Fallback: scan file .opf di root
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && str_ends_with($name, '.opf')) {
                return $name;
            }
        }

        return null;
    }
}
