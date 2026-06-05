<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $system = "Kamu adalah penulis blog profesional berbahasa Indonesia untuk bukudigi.com, "
            . "marketplace ebook penulis Indonesia. Kamu menulis artikel yang informatif, "
            . "terstruktur, enak dibaca, dan SEO-friendly. Gunakan HTML sederhana "
            . "(<p>, <h2>, <h3>, <ul>, <li>, <strong>, <em>). JANGAN sertakan <html>, <head>, "
            . "<body>, atau <h1> (judul sudah terpisah). Selalu balas HANYA dengan JSON valid, "
            . "tanpa penjelasan tambahan, tanpa markdown fence.";

        $user = "Tulis artikel blog dengan judul: \"{$title}\".\n"
            . "Topik umum: \"{$topic}\".\n"
            . "Target panjang: sekitar {$words} kata.\n\n"
            . "Struktur: paragraf pembuka yang menarik, beberapa subjudul <h2>/<h3>, "
            . "isi yang padat & praktis, dan paragraf penutup. Sertakan ajakan halus "
            . "untuk membaca/menjual ebook di bukudigi.com bila relevan (jangan memaksa).\n\n"
            . "Balas HANYA dalam format JSON object dengan struktur PERSIS:\n"
            . '{"content_html": "<p>...</p>", "excerpt": "ringkasan 1-2 kalimat", "meta_description": "deskripsi SEO maks 155 karakter"}';

        // Hitung max_tokens proporsional dgn target kata (1 kata ~ 1.5 token + overhead HTML)
        $maxTokens = (int) min(8000, max(1500, $words * 4));

        $data = $this->call($system, $user, maxTokens: $maxTokens);

        $json = $this->extractJson($data);
        $parsed = json_decode($json, true);

        if (! is_array($parsed) || empty($parsed['content_html'])) {
            throw new RuntimeException('Format artikel dari AI tidak valid.');
        }

        return [
            'content_html'     => (string) $parsed['content_html'],
            'excerpt'          => trim((string) ($parsed['excerpt'] ?? '')),
            'meta_description' => trim((string) ($parsed['meta_description'] ?? '')),
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
