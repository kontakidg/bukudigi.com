<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

/**
 * Watermark EPUB via inject metadata tag dc:rights + dc:contributor
 * di content.opf. Pakai DOMDocument (proper XML parser) supaya tidak corrupt file.
 *
 * Kalau XML parsing gagal, fallback: copy master as-is (deliver unwatermarked
 * tapi tetap readable) supaya UX tidak putus.
 */
class EpubWatermarkService
{
    public function apply(string $masterPath, string $destPath, array $buyer): void
    {
        if (! is_file($masterPath)) {
            throw new RuntimeException("EPUB master not found: {$masterPath}");
        }

        // 1) Validasi master EPUB readable sebagai zip
        $check = new ZipArchive();
        if ($check->open($masterPath) !== true) {
            throw new RuntimeException("EPUB master corrupt (not a valid zip): {$masterPath}");
        }
        $check->close();

        // 2) Copy master ke destinasi (kita modify in-place di copy)
        $destDir = dirname($destPath);
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        if (! @copy($masterPath, $destPath)) {
            throw new RuntimeException("Failed to copy EPUB to {$destPath}");
        }

        // 3) Try inject watermark. Kalau gagal di langkah manapun, fallback ke unwatermarked copy.
        try {
            $this->injectMetadata($destPath, $buyer);
        } catch (\Throwable $e) {
            Log::warning('[EpubWatermark] Inject failed, delivering as-is', [
                'master' => $masterPath,
                'error' => $e->getMessage(),
            ]);
            // File hasil = copy persis master (sudah di-copy di step 2). OK as fallback.
        }
    }

    private function injectMetadata(string $epubPath, array $buyer): void
    {
        $zip = new ZipArchive();
        if ($zip->open($epubPath) !== true) {
            throw new RuntimeException('Failed to reopen EPUB copy');
        }

        $opfPath = $this->findOpfPath($zip);
        if (! $opfPath) {
            $zip->close();
            throw new RuntimeException('content.opf not found in EPUB');
        }

        $opfXml = $zip->getFromName($opfPath);
        if ($opfXml === false || empty($opfXml)) {
            $zip->close();
            throw new RuntimeException("Failed to read {$opfPath}");
        }

        // Parse pakai DOMDocument supaya tidak korup XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        // Suppress XML warnings — we'll detect parse failure via loadXML return
        $prevLibxml = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($opfXml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevLibxml);

        if (! $loaded) {
            $zip->close();
            throw new RuntimeException('content.opf is not valid XML');
        }

        $metadataNodes = $dom->getElementsByTagName('metadata');
        if ($metadataNodes->length === 0) {
            $zip->close();
            throw new RuntimeException('<metadata> tag missing in content.opf');
        }
        $metadata = $metadataNodes->item(0);

        // Hapus watermark lama (kalau ada dari run sebelumnya, biar idempotent)
        $existingIds = ['bukudigi-buyer', 'bukudigi-rights'];
        $toRemove = [];
        foreach ($metadata->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && in_array($child->getAttribute('id'), $existingIds, true)) {
                $toRemove[] = $child;
            }
        }
        foreach ($toRemove as $node) {
            $metadata->removeChild($node);
        }

        // Build watermark elements pakai DOM proper. Pakai createTextNode supaya
        // karakter spesial (<, >, &) di-escape otomatis sesuai XML spec.
        $name = $this->stripUnsafe($buyer['name'] ?? 'Anonim');
        $email = $this->stripUnsafe($buyer['email'] ?? '-');
        $orderCode = $this->stripUnsafe($buyer['order_code'] ?? '-');
        $timestamp = date('Y-m-d H:i');

        $dcNs = 'http://purl.org/dc/elements/1.1/';

        $contributor = $dom->createElementNS($dcNs, 'dc:contributor');
        $contributor->setAttribute('id', 'bukudigi-buyer');
        $contributor->appendChild($dom->createTextNode("{$name} ({$email})"));
        $metadata->appendChild($contributor);

        $rights = $dom->createElementNS($dcNs, 'dc:rights');
        $rights->setAttribute('id', 'bukudigi-rights');
        $rights->appendChild($dom->createTextNode(
            "Dibeli oleh {$name} ({$email}) - Order {$orderCode} - {$timestamp} - via bukudigi.com"
        ));
        $metadata->appendChild($rights);

        $newOpf = $dom->saveXML();
        if (! $newOpf) {
            $zip->close();
            throw new RuntimeException('Failed to serialize modified content.opf');
        }

        // Replace dalam zip (addFromString overwrite filename yang sudah ada)
        $zip->addFromString($opfPath, $newOpf);
        $zip->close();

        // Verify hasil masih readable
        $verify = new ZipArchive();
        if ($verify->open($epubPath) !== true) {
            throw new RuntimeException('EPUB corrupt setelah modifikasi');
        }
        if ($verify->locateName('META-INF/container.xml') === false) {
            $verify->close();
            throw new RuntimeException('container.xml hilang setelah modifikasi');
        }
        $verify->close();
    }

    /**
     * Strip karakter yang berisiko break XML serialization meski sudah ke-escape.
     * (defensive — DOMDocument createTextNode harusnya safe, tapi extra layer.)
     */
    private function stripUnsafe(string $s): string
    {
        // Hapus karakter control + angle brackets (paranoid)
        $s = preg_replace('/[\x00-\x1F\x7F<>]/u', '', $s);
        return trim($s);
    }

    private function findOpfPath(ZipArchive $zip): ?string
    {
        $container = $zip->getFromName('META-INF/container.xml');
        if ($container === false) {
            return null;
        }

        if (preg_match('#full-path="([^"]+\.opf)"#u', $container, $m)) {
            return $m[1];
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && str_ends_with($name, '.opf')) {
                return $name;
            }
        }

        return null;
    }
}
