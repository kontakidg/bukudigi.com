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
            && ! empty(config('services.facebook.page_access_token'))
            && ! empty(config('services.facebook.app_secret'));
    }

    /**
     * Generate appsecret_proof — wajib untuk server-side API calls.
     * HMAC-SHA256 dari access token pakai app_secret sebagai key.
     */
    protected function appsecretProof(): string
    {
        return hash_hmac(
            'sha256',
            config('services.facebook.page_access_token'),
            config('services.facebook.app_secret')
        );
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

        // Potong deskripsi maks 200 karakter
        $desc = strip_tags($book->description ?? '');
        $desc = mb_strlen($desc) > 200 ? mb_substr($desc, 0, 197) . '…' : $desc;

        $message = "📚 Buku baru tersedia di bukudigi.com!\n\n"
            . "✦ {$book->title}\n"
            . "✍️ oleh {$book->displayAuthor()}"
            . ($book->category ? " · {$book->category->name}" : '') . "\n\n"
            . ($desc ? "{$desc}\n\n" : '')
            . "Penasaran? Baca 5 halaman pertama gratis — langsung di website tanpa daftar.\n\n"
            . "👉 {$bookUrl}\n\n"
            . "#ebook #bukudigital #bukudigi"
            . ($book->category ? " #" . $this->toHashtag($book->category->name) : '')
            . " #bacanline #ebookindonesia";

        // Post dengan foto cover (kalau ada) pakai endpoint /photos
        // Kalau tidak ada cover, pakai /feed dengan link
        $token  = config('services.facebook.page_access_token');
        $proof  = $this->appsecretProof();
        $pageId = config('services.facebook.page_id');

        // Pakai /feed + link — Facebook auto-scrape cover dari OG tag halaman buku.
        // Lebih sedikit permission dibanding /photos, cukup pages_manage_posts.
        $response = Http::timeout(15)->post(
            "{$this->graphUrl}/{$pageId}/feed",
            [
                'message'         => $message,
                'link'            => $bookUrl,
                'access_token'    => $token,
                'appsecret_proof' => $proof,
            ]
        );

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
