<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Anthropic Claude — Messages API (raw HTTP via Http facade, konsisten dgn
 * PaypalService/FacebookPageService; project ini tidak pakai SDK).
 *
 * Endpoint : POST https://api.anthropic.com/v1/messages
 * Header   : x-api-key, anthropic-version: 2023-06-01
 * Model    : config services.anthropic.model (default claude-sonnet-4-6)
 *
 * Catatan: temperature/thinking sengaja TIDAK dikirim agar model-agnostic
 * (Opus 4.7+ menolak temperature) & cepat/murah untuk tugas JSON.
 */
class AnthropicService
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const VERSION  = '2023-06-01';

    public function isActive(): bool
    {
        return ! empty(config('services.anthropic.api_key'));
    }

    private function model(): string
    {
        return config('services.anthropic.model', 'claude-sonnet-4-6');
    }

    /**
     * Generate N judul artikel SEO bahasa Indonesia untuk satu topik.
     * Return array of string (judul). Throw RuntimeException kalau gagal.
     *
     * @return array<string>
     */
    public function generateTitles(string $topic, int $count): array
    {
        $count = max(1, min(20, $count));

        $system = "Kamu adalah editor konten blog berbahasa Indonesia untuk bukudigi.com, "
            . "marketplace ebook penulis Indonesia. Tugasmu membuat judul artikel blog yang "
            . "menarik, SEO-friendly, dan relevan. Selalu balas HANYA dengan JSON valid, "
            . "tanpa penjelasan, tanpa markdown fence.";

        $user = "Buat {$count} judul artikel blog yang berbeda untuk topik: \"{$topic}\".\n\n"
            . "Aturan:\n"
            . "- Bahasa Indonesia, gaya menarik & natural (bukan clickbait berlebihan)\n"
            . "- Panjang 40-70 karakter\n"
            . "- Variasikan sudut pandang (tips, panduan, daftar, opini, dll)\n\n"
            . "Balas HANYA dalam format JSON array string, contoh: "
            . '["Judul pertama", "Judul kedua"]';

        $data = $this->call($system, $user, maxTokens: 1024);

        $json = $this->extractJson($data);
        $parsed = json_decode($json, true);

        if (! is_array($parsed)) {
            throw new RuntimeException('Format judul dari AI tidak valid.');
        }

        // Normalisasi: ambil string saja, trim, buang kosong
        $titles = [];
        foreach ($parsed as $item) {
            if (is_string($item)) {
                $t = trim($item);
            } elseif (is_array($item) && isset($item['title'])) {
                $t = trim((string) $item['title']);
            } else {
                continue;
            }
            if ($t !== '') {
                $titles[] = $t;
            }
        }

        if (empty($titles)) {
            throw new RuntimeException('AI tidak menghasilkan judul.');
        }

        return array_slice($titles, 0, $count);
    }

    /**
     * Generate isi artikel lengkap (HTML) + excerpt + meta_description.
     *
     * @return array{content_html:string, excerpt:string, meta_description:string}
     */
    public function generateArticle(string $title, string $topic, int $words = 800): array
    {
        // Pakai format DELIMITER (bukan JSON) — HTML panjang di JSON sering rusak
        // karena newline/quote tak ter-escape. Penanda = nol escaping, tahan banting.
        $system = "Kamu adalah penulis blog profesional berbahasa Indonesia untuk bukudigi.com, "
            . "marketplace ebook penulis Indonesia. Kamu menulis artikel informatif, terstruktur, "
            . "enak dibaca, dan SEO-friendly. Gunakan HTML sederhana "
            . "(<p>, <h2>, <h3>, <ul>, <li>, <strong>, <em>). JANGAN sertakan <html>, <head>, "
            . "<body>, atau <h1> (judul sudah terpisah). JANGAN bungkus dengan markdown fence.\n\n"
            . "Balas PERSIS dengan format penanda berikut, tanpa teks lain di luar penanda:\n"
            . "===META===\n"
            . "(satu baris meta description SEO, maksimal 155 karakter)\n"
            . "===EXCERPT===\n"
            . "(satu sampai dua kalimat ringkasan artikel)\n"
            . "===CONTENT===\n"
            . "(isi lengkap artikel dalam HTML)";

        $user = "Tulis artikel blog dengan judul: \"{$title}\".\n"
            . "Topik umum: \"{$topic}\".\n"
            . "Target panjang: sekitar {$words} kata.\n\n"
            . "Struktur: paragraf pembuka menarik, beberapa subjudul <h2>/<h3>, isi padat & praktis, "
            . "paragraf penutup. Sertakan ajakan halus untuk membaca/menjual ebook di bukudigi.com "
            . "bila relevan (jangan memaksa).\n\n"
            . "Ingat: balas PERSIS dengan penanda ===META===, ===EXCERPT===, ===CONTENT=== seperti instruksi.";

        // max_tokens proporsional (1 kata ~ 1.5 token + overhead HTML)
        $maxTokens = (int) min(8000, max(1500, $words * 4));

        $text = $this->call($system, $user, maxTokens: $maxTokens);

        return $this->parseArticleSections($text);
    }

    /**
     * Parse respons berpenanda ===META===/===EXCERPT===/===CONTENT===.
     * Fallback robust: kalau penanda CONTENT tidak ada, pakai seluruh teks
     * sebagai content (lebih baik daripada gagal total).
     *
     * @return array{content_html:string, excerpt:string, meta_description:string}
     */
    private function parseArticleSections(string $text): array
    {
        // Buang markdown fence kalau model nakal membungkusnya
        $text = preg_replace('/```[a-z]*\s*/i', '', $text);
        $text = str_replace('```', '', $text);

        $meta = '';
        $excerpt = '';
        $content = '';

        // Tangkap tiap section pakai regex (penanda bisa di awal baris)
        if (preg_match('/===\s*META\s*===\s*(.*?)\s*===\s*EXCERPT\s*===/is', $text, $m)) {
            $meta = trim($m[1]);
        }
        if (preg_match('/===\s*EXCERPT\s*===\s*(.*?)\s*===\s*CONTENT\s*===/is', $text, $m)) {
            $excerpt = trim($m[1]);
        }
        if (preg_match('/===\s*CONTENT\s*===\s*(.*)$/is', $text, $m)) {
            $content = trim($m[1]);
        }

        // Fallback: tidak ada penanda CONTENT → anggap seluruh teks (tanpa baris penanda) sbg HTML
        if ($content === '') {
            $content = trim(preg_replace('/===\s*(META|EXCERPT|CONTENT)\s*===/i', '', $text));
        }

        if ($content === '') {
            throw new RuntimeException('AI mengembalikan artikel kosong.');
        }

        // Excerpt fallback: ambil teks dari paragraf pertama
        if ($excerpt === '') {
            $excerpt = Str::limit(trim(strip_tags($content)), 160);
        }

        return [
            'content_html'     => $content,
            'excerpt'          => $excerpt,
            'meta_description' => $meta,
        ];
    }

    /**
     * Panggil Messages API, return teks dari content block pertama bertipe text.
     */
    private function call(string $system, string $user, int $maxTokens): string
    {
        if (! $this->isActive()) {
            throw new RuntimeException('Anthropic API key belum dikonfigurasi.');
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key'         => config('services.anthropic.api_key'),
                'anthropic-version' => self::VERSION,
                'content-type'      => 'application/json',
            ])
            ->post(self::ENDPOINT, [
                'model'      => $this->model(),
                'max_tokens' => $maxTokens,
                // System sebagai array block + cache_control (prompt caching antar artikel).
                'system' => [[
                    'type' => 'text',
                    'text' => $system,
                    'cache_control' => ['type' => 'ephemeral'],
                ]],
                'messages' => [[
                    'role'    => 'user',
                    'content' => $user,
                ]],
            ]);

        if (! $response->successful()) {
            Log::warning('[Anthropic] request gagal', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);
            $msg = $response->json('error.message') ?? ('HTTP '.$response->status());
            throw new RuntimeException('Anthropic: '.$msg);
        }

        // content adalah array of block; ambil text block pertama
        $blocks = $response->json('content', []);
        $text = '';
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Anthropic mengembalikan respons kosong.');
        }

        return $text;
    }

    /**
     * Bersihkan respons agar jadi JSON murni: buang markdown fence,
     * ambil dari kurung pembuka pertama ({ atau [) sampai pasangannya.
     */
    private function extractJson(string $text): string
    {
        // Buang ```json ... ``` fence kalau ada
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', trim($text));
        $text = trim($text);

        // Cari awal JSON (object atau array)
        $firstBrace  = strpos($text, '{');
        $firstSquare = strpos($text, '[');

        $candidates = array_filter([$firstBrace, $firstSquare], fn ($v) => $v !== false);
        if (empty($candidates)) {
            return $text; // biar json_decode yang gagal & lempar error di pemanggil
        }
        $start = min($candidates);

        // Ambil dari $start sampai penutup terakhir yang sesuai
        $open  = $text[$start];
        $close = $open === '{' ? '}' : ']';
        $lastClose = strrpos($text, $close);

        if ($lastClose !== false && $lastClose > $start) {
            return substr($text, $start, $lastClose - $start + 1);
        }

        return substr($text, $start);
    }
}
