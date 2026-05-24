<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
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

    public function codes(): HasMany
    {
        return $this->hasMany(AffiliateCode::class);
    }

    public function defaultCode()
    {
        return $this->hasOne(AffiliateCode::class)->where('is_default', true);
    }

    /**
     * Pastikan affiliate punya minimal 1 code (default). Buat kalau belum ada.
     */
    public function ensureDefaultCode(): AffiliateCode
    {
        $existing = $this->defaultCode()->first();
        if ($existing) {
            return $existing;
        }

        return $this->codes()->create([
            'code' => AffiliateCode::generateUniqueCode($this->user->name ?? 'AFF'),
            'label' => 'Default',
            'is_default' => true,
        ]);
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
}
