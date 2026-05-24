<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_purchase_amount',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'valid_from',
        'valid_until',
        'applicable_book_ids',
        'applicable_category_ids',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'max_discount_amount' => 'integer',
            'min_purchase_amount' => 'integer',
            'max_uses' => 'integer',
            'max_uses_per_user' => 'integer',
            'used_count' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'applicable_book_ids' => 'array',
            'applicable_category_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    /**
     * Apakah voucher sedang dalam masa berlaku.
     */
    public function isWithinValidPeriod(): bool
    {
        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }
        return true;
    }

    /**
     * Apakah voucher masih punya kuota total.
     */
    public function hasRemainingQuota(): bool
    {
        if ($this->max_uses === null) {
            return true;
        }
        return $this->used_count < $this->max_uses;
    }

    /**
     * Cek apakah user $userId masih bisa pakai voucher ini.
     */
    public function canBeUsedByUser(int $userId): bool
    {
        $usageCount = $this->usages()->where('user_id', $userId)->count();
        return $usageCount < $this->max_uses_per_user;
    }

    /**
     * Cek apakah voucher berlaku untuk buku tertentu.
     */
    public function appliesToBook(Book $book): bool
    {
        // Spesifik book IDs
        if (! empty($this->applicable_book_ids)) {
            if (in_array($book->id, $this->applicable_book_ids)) {
                return true;
            }
        }
        // Spesifik category IDs
        if (! empty($this->applicable_category_ids)) {
            if ($book->category_id && in_array($book->category_id, $this->applicable_category_ids)) {
                return true;
            }
        }
        // Kalau dua-duanya null → berlaku untuk semua
        return empty($this->applicable_book_ids) && empty($this->applicable_category_ids);
    }

    /**
     * Hitung discount rupiah untuk gross amount tertentu.
     */
    public function calculateDiscount(int $grossAmount): int
    {
        if ($this->discount_type === 'percentage') {
            $discount = (int) round($grossAmount * ($this->discount_value / 100));
            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }
        } else {
            $discount = $this->discount_value;
        }

        // Discount ga boleh lebih dari gross amount (mencegah negative price)
        if ($discount > $grossAmount) {
            $discount = $grossAmount;
        }

        return $discount;
    }

    /**
     * Format display untuk UI (e.g. "Diskon 20%" atau "Potongan Rp 5.000").
     */
    public function displayDiscount(): string
    {
        if ($this->discount_type === 'percentage') {
            return "Diskon {$this->discount_value}%";
        }
        return 'Potongan Rp '.number_format($this->discount_value, 0, ',', '.');
    }
}
