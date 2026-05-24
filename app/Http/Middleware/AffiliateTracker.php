<?php

namespace App\Http\Middleware;

use App\Models\AffiliateClick;
use App\Models\AffiliateCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tangkap parameter ?ref=<kode> dari URL.
 * Set cookie 30 hari + log AffiliateClick (dedup per ip+code harian).
 * Cookie name: bd_aff (value = affiliate code string).
 * Self/owner referrer diabaikan saat user login.
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
            $affCode = AffiliateCode::with('affiliate')->where('code', $code)->first();

            if ($affCode && $affCode->affiliate && $affCode->affiliate->status === 'approved') {
                $affiliate = $affCode->affiliate;
                $userId = $request->user()?->id;
                $isSelf = $userId && (int) $affiliate->user_id === (int) $userId;

                if (! $isSelf) {
                    // Set cookie 30 hari
                    Cookie::queue(
                        self::COOKIE_NAME,
                        $affCode->code,
                        self::COOKIE_DAYS * 24 * 60
                    );

                    // Log click — dedup 1x per ip+code+hari
                    $ip = $request->ip();
                    $uaHash = substr(md5((string) $request->userAgent()), 0, 32);
                    $today = Carbon::today();

                    $exists = AffiliateClick::where('affiliate_code_id', $affCode->id)
                        ->where('ip', $ip)
                        ->where('clicked_at', '>=', $today)
                        ->exists();

                    if (! $exists) {
                        $bookId = null;
                        $book = $request->route('book');
                        if ($book && is_object($book) && isset($book->id)) {
                            $bookId = $book->id;
                        }

                        AffiliateClick::create([
                            'affiliate_id' => $affiliate->id,
                            'affiliate_code_id' => $affCode->id,
                            'book_id' => $bookId,
                            'ip' => $ip,
                            'ua_hash' => $uaHash,
                            'referer' => substr((string) $request->headers->get('referer'), 0, 500),
                            'clicked_at' => now(),
                        ]);

                        $affCode->increment('clicks_count');
                        $affiliate->increment('clicks_count');
                    }
                }
            }
        }

        return $next($request);
    }
}
