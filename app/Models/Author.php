<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Author extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'nik',
        'npwp',
        'bank_name',
        'bank_account',
        'bank_holder',
        'ktp_image_path',
        'selfie_image_path',
        'status',
        'rejection_reason',
        'verified_at',
        'balance_available',
        'balance_pending',
        'total_earned',
        'last_payout_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'last_payout_at' => 'datetime',
            'balance_available' => 'integer',
            'balance_pending' => 'integer',
            'total_earned' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function penNames(): HasMany
    {
        return $this->hasMany(PenName::class);
    }

    public function defaultPenName(): HasOne
    {
        return $this->hasOne(PenName::class)->where('is_default', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hasNpwp(): bool
    {
        return ! empty($this->npwp);
    }

    public function pph23Rate(): float
    {
        return $this->hasNpwp() ? 0.02 : 0.04;
    }
}
