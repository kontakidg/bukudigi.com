<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'status',
        'promo_channels',
        'motivation',
        'commitment_agreed',
        'rejection_reason',
        'admin_note',
        'commission_rate',
        'balance_available',
        'balance_pending',
        'total_earned',
        'clicks_count',
        'conversions_count',
        'approved_at',
        'approved_by_id',
        'last_payout_at',
    ];

    protected function casts(): array
    {
        return [
            'commitment_agreed' => 'boolean',
            'commission_rate' => 'decimal:2',
            'balance_available' => 'integer',
            'balance_pending' => 'integer',
            'total_earned' => 'integer',
            'clicks_count' => 'integer',
            'conversions_count' => 'integer',
            'approved_at' => 'datetime',
            'last_payout_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(AffiliateEarning::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
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
