<?php

namespace App\Services;

use App\Jobs\PostArticleToFacebookJob;
use App\Models\Article;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bulk import artikel dari CSV + gambar dari ZIP.
 *
 * Kolom CSV (header wajib, urutan bebas):
 *   title, excerpt, content, category, author_name,
 *   cover_filename, status, published_at, meta_title, meta_description
 *
 * - cover_filename = nama file di dalam ZIP (mis. "cover1.jpg")
 * - status: draft|scheduled|published|archived (default draft)
 * - published_at: format "Y-m-d H:i" atau "Y-m-d" (opsional)
 */
class ArticleImportService
{
    private const COLUMNS = [
        'title', 'excerpt', 'content', 'category', 'author_name',
        'cover_filename', 'status', 'published_at', 'meta_title', 'meta_description',
    ];

    /**
     * @return array{imported:int, skipped:int, errors:array<string>}
     */
    public function import(string $csvAbsolutePath, ?string $zipAbsolutePath = null): array
    {
        $errors   = [];
        $imported = 0;
        $skipped  = 0;

        // 1. Extract ZIP → map cover_filename => public storage path
        $coverMap = [];
        if ($zipAbsolutePath && is_file($zipAbsolutePath)) {
            [$coverMap, $zipErrors] = $this->extractZipCovers($zipAbsolutePath);
            $errors = array_merge($errors, $zipErrors);
        }

        // 2. Parse CSV
        if (! is_file($csvAbsolutePath)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['File CSV tidak ditemukan.']];
        }

        $handle = fopen($csvAbsolutePath, 'r');
        if (! $handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Gagal membuka file CSV.']];
        }

        // Header
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV kosong / tidak ada header.']];
        }
        // Normalisasi header (lowercase, trim, strip BOM)
        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))), $header);

        if (! in_array('title', $header, true)) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Kolom "title" wajib ada di header CSV.']];
        }

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Skip baris kosong
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = $this->mapRow($header, $row);

            if (empty(trim($data['title'] ?? ''))) {
                $skipped++;
                $errors[] = "Baris {$rowNum}: judul kosong, dilewati.";
                continue;
            }

            try {
                $article = $this->createArticle($data, $coverMap);
                $imported++;

                if ($article->status === 'published') {
                    PostArticleToFacebookJob::dispatch($article->id);
                }
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Baris {$rowNum} ({$data['title']}): {$e->getMessage()}";
            }
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /** Map row array → assoc berdasarkan header. */
    private function mapRow(array $header, array $row): array
    {
        $data = [];
        foreach (self::COLUMNS as $col) {
            $idx = array_search($col, $header, true);
            $data[$col] = $idx !== false && isset($row[$idx]) ? trim((string) $row[$idx]) : null;
        }
        return $data;
    }

    private function createArticle(array $data, array $coverMap): Article
    {
        $status = in_array($data['status'] ?? '', ['draft', 'scheduled', 'published', 'archived'], true)
            ? $data['status']
            : 'draft';

        $publishedAt = $this->parseDate($data['published_at'] ?? null);
        // Kalau published tapi tanggal kosong → set sekarang
        if ($status === 'published' && ! $publishedAt) {
            $publishedAt = now();
        }

        $coverPath = null;
        if (! empty($data['cover_filename'])) {
            $coverPath = $coverMap[$data['cover_filename']]
                ?? $coverMap[basename($data['cover_filename'])]
                ?? null;
        }

        return Article::create([
            'title'            => $data['title'],
            'excerpt'          => $data['excerpt'] ?: null,
            'content'          => $data['content'] ?: '',
            'category'         => $data['category'] ?: null,
            'author_name'      => $data['author_name'] ?: 'Tim bukudigi.com',
            'cover_path'       => $coverPath,
            'status'           => $status,
            'published_at'     => $publishedAt,
            'meta_title'       => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'created_by'       => auth()->id(),
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extract gambar dari ZIP ke public disk article-covers/.
     * @return array{0: array<string,string>, 1: array<string>}  [map filename=>path, errors]
     */
    private function extractZipCovers(string $zipPath): array
    {
        $map    = [];
        $errors = [];

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [[], ['Gagal membuka file ZIP.']];
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! $name || str_ends_with($name, '/')) {
                continue; // skip folder
            }

            $base = basename($name);
            // skip file sistem mac (__MACOSX, .DS_Store)
            if (str_starts_with($name, '__MACOSX') || str_starts_with($base, '.')) {
                continue;
            }

            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowedExt, true)) {
                continue;
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                $errors[] = "Gagal membaca gambar: {$base}";
                continue;
            }

            // Simpan ke public disk dengan nama unik agar tidak tabrakan
            $storedName = 'article-covers/' . Str::random(8) . '-' . Str::slug(pathinfo($base, PATHINFO_FILENAME)) . '.' . $ext;
            Storage::disk('public')->put($storedName, $contents);

            // Map pakai nama asli (full path dalam zip) + basename
            $map[$name]  = $storedName;
            $map[$base]  = $storedName;
        }

        $zip->close();

        return [$map, $errors];
    }

    /** CSV template (header + 1 contoh baris) untuk di-download. */
    public static function templateCsv(): string
    {
        $header = implode(',', self::COLUMNS);
        $example = implode(',', [
            '"Tips Menulis Ebook Pertama"',
            '"Panduan singkat memulai ebook"',
            '"<p>Ini isi artikel dalam HTML. Bisa <strong>bold</strong>, list, dll.</p>"',
            '"Tips Menulis"',
            '"Tim bukudigi.com"',
            'cover1.jpg',
            'published',
            '2026-06-05 09:00',
            '"Tips Menulis Ebook Pertama | bukudigi"',
            '"Pelajari cara menulis dan menerbitkan ebook pertamamu di bukudigi.com"',
        ]);

        return $header . "\n" . $example . "\n";
    }
}
