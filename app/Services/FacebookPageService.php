<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Post otomatis ke Facebook Page saat buku di-approve.
 * Pakai Graph API v19 — endpoint /feed dengan link preview (FB scrape OG tag).
 *
 * Setup:
 *   1. Buat Facebook App di developers.facebook.com
 *   2. Minta permission pages_manage_posts + pages_read_engagement
 *   3. Generate long-lived Page Access Token (tidak expired)
 *   4. Isi .env: FACEBOOK_PAGE_ID + FACEBOOK_PAGE_ACCESS_TOKEN
 */
class FacebookPageService
{
    protected string $graphUrl = 'https://graph.facebook.com/v19.0';

    public function isActive(): bool
    {
        return ! empty(config('services.facebook.page_id'))
            && ! empty(config('services.facebook.page_access_token'));
    }

    /**
     * Post buku baru ke Facebook Page.
     * Return post ID kalau sukses, null kalau gagal/nonaktif.
     */
    public function postBook(Book $book): ?string
    {
        if (! $this->isActive()) {
            return null;
        }

        $book->loadMissing('author', 'category');

        $bookUrl  = route('books.show', $book->slug);
        $price    = $book->price > 0
            ? 'Rp ' . number_format($book->price, 0, ',', '.')
            : 'GRATIS';

        // Cover image URL (public)
        $coverUrl = null;
        if ($book->og_card_path) {
            $coverUrl = \Illuminate\Support\Str::startsWith($book->og_card_path, ['http://', 'https://'])
                ? $book->og_card_path
                : url('storage/' . $book->og_card_path);
        } elseif ($book->cover_path) {
            $coverUrl = \Illuminate\Support\Str::startsWith($book->cover_path, ['http://', 'https://'])
                ? $book->cover_path
                : url('storage/' . $book->cover_path);
        }

        // Potong deskripsi maks 200 karakter
        $desc = strip_tags($book->description ?? '');
        $desc = mb_strlen($desc) > 200 ? mb_substr($desc, 0, 197) . '…' : $desc;

        $message = "📚 Buku baru di bukudigi.com!\n\n"
            . "📖 *{$book->title}*\n"
            . "✍️ {$book->displayAuthor()}\n"
            . ($book->category ? "🏷️ {$book->category->name}\n" : '')
            . "💰 {$price}\n\n"
            . ($desc ? "{$desc}\n\n" : '')
            . "👉 Baca preview & beli: {$bookUrl}\n\n"
            . "#ebook #bukudigital #bukudigi #" . $this->toHashtag($book->category?->name ?? 'buku');

        // Post dengan foto cover (kalau ada) pakai endpoint /photos
        // Kalau tidak ada cover, pakai /feed dengan link
        if ($coverUrl) {
            $response = Http::timeout(15)->post(
                "{$this->graphUrl}/" . config('services.facebook.page_id') . "/photos",
                [
                    'url'          => $coverUrl,
                    'caption'      => $message,
                    'access_token' => config('services.facebook.page_access_token'),
                ]
            );
        } else {
            $response = Http::timeout(15)->post(
                "{$this->graphUrl}/" . config('services.facebook.page_id') . "/feed",
                [
                    'message'      => $message,
                    'link'         => $bookUrl,
                    'access_token' => config('services.facebook.page_access_token'),
                ]
            );
        }

        if ($response->successful()) {
            $postId = $response->json('post_id') ?? $response->json('id');
            Log::info('[Facebook] Buku dipost', [
                'book'    => $book->slug,
                'post_id' => $postId,
            ]);
            return $postId;
        }

        Log::warning('[Facebook] Post gagal', [
            'book'   => $book->slug,
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        return null;
    }

    private function toHashtag(?string $str): string
    {
        if (! $str) return 'buku';
        return preg_replace('/[^a-zA-Z0-9]/', '', str_replace(' ', '', ucwords(strtolower($str))));
    }
}
