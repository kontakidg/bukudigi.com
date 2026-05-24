<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AffiliateCode extends Model
{
    public const MAX_PER_AFFILIATE = 10;

    protected $fillable = [
        'affiliate_id',
        'code',
        'label',
        'is_default',
        'clicks_count',
        'conversions_count',
        'total_earned',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'clicks_count' => 'integer',
            'conversions_count' => 'integer',
            'total_earned' => 'integer',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(AffiliateEarning::class);
    }

    public function shareUrl(?string $bookSlug = null): string
    {
        $base = $bookSlug
            ? route('books.show', ['book' => $bookSlug])
            : route('home');
        $sep = str_contains($base, '?') ? '&' : '?';
        return $base.$sep.'ref='.$this->code;
    }

    public static function generateUniqueCode(string $seed = ''): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $seed), 0, 6));
        if (strlen($base) < 3) {
            $base = 'AFF';
        }
        $tries = 0;
        do {
            $code = $base.Str::upper(Str::random(4));
            $tries++;
            if ($tries > 20) {
                $code = 'AFF'.Str::upper(Str::random(8));
                break;
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
