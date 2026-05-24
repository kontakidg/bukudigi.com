<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tangkap parameter ?ref=<kode> dari URL.
 * Set cookie 30 hari + log AffiliateClick (dedup per ip+affiliate harian).
 *
 * Cookie name: bd_aff (value = affiliate code).
 * Self/owner referrer diabaikan (affiliate ga boleh refer dirinya sendiri saat login).
 */
class AffiliateTracker
{
    public const COOKIE_NAME = 'bd_aff';
    public const COOKIE_DAYS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $code = trim((string) $request->query('ref', ''));

        if ($code !== '' && strlen($code) <= 32) {
            $code = strtoupper($code);
            $affiliate = Affiliate::where('code', $code)
                ->where('status', 'approved')
                ->first();

            if ($affiliate) {
                $userId = $request->user()?->id;
                $isSelf = $userId && (int) $affiliate->user_id === (int) $userId;

                if (! $isSelf) {
                    // Set cookie 30 hari
                    Cookie::queue(
                        self::COOKIE_NAME,
                        $affiliate->code,
                        self::COOKIE_DAYS * 24 * 60
                    );

                    // Log click — dedup 1x per ip+affiliate+hari
                    $ip = $request->ip();
                    $uaHash = substr(md5((string) $request->userAgent()), 0, 32);
                    $today = Carbon::today();

                    $exists = AffiliateClick::where('affiliate_id', $affiliate->id)
                        ->where('ip', $ip)
                        ->where('clicked_at', '>=', $today)
                        ->exists();

                    if (! $exists) {
                        $bookId = null;
                        // Coba ambil book dari route binding (kalau ini URL /buku/{slug})
                        $book = $request->route('book');
                        if ($book && is_object($book) && isset($book->id)) {
                            $bookId = $book->id;
                        }

                        AffiliateClick::create([
                            'affiliate_id' => $affiliate->id,
                            'book_id' => $bookId,
                            'ip' => $ip,
                            'ua_hash' => $uaHash,
                            'referer' => substr((string) $request->headers->get('referer'), 0, 500),
                            'clicked_at' => now(),
                        ]);

                        // Increment cached counter
                        $affiliate->increment('clicks_count');
                    }
                }
            }
        }

        return $next($request);
    }
}
