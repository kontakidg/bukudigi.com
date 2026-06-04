<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'user_id',
        'book_id',
        'author_id',
        'gross_amount',
        'voucher_id',
        'voucher_discount',
        'affiliate_id',
        'affiliate_code',
        'affiliate_code_id',
        'affiliate_commission',
        'net_amount',
        'commission',
        'author_earning',
        'author_earning_credited',
        'gateway_fee',
        'status',
        'payment_gateway',
        'midtrans_order_id',
        'payment_method',
        'midtrans_response',
        'paypal_order_id',
        'paypal_capture_id',
        'paypal_amount_usd',
        'paypal_usd_rate',
        'paid_at',
        'watermarked_pdf_path',
        'watermarked_epub_path',
        'watermarked_at',
        'download_expires_at',
        'download_count',
        'refunded_at',
        'refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'midtrans_response' => 'array',
            'paid_at' => 'datetime',
            'watermarked_at' => 'datetime',
            'download_expires_at' => 'datetime',
            'refunded_at' => 'datetime',
            'gross_amount' => 'integer',
            'voucher_discount' => 'integer',
            'net_amount' => 'integer',
            'commission' => 'integer',
            'affiliate_commission' => 'integer',
            'author_earning' => 'integer',
            'author_earning_credited' => 'boolean',
            'gateway_fee' => 'integer',
            'download_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_code)) {
                $order->order_code = 'BD-' . strtoupper(Str::random(10));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function affiliateCode(): BelongsTo
    {
        return $this->belongsTo(AffiliateCode::class);
    }

    public function affiliateEarning()
    {
        return $this->hasOne(AffiliateEarning::class);
    }

    public function getRouteKeyName(): string
    {
        return 'order_code';
    }

    public function canDownload(): bool
    {
        if ($this->status !== 'ready') {
            return false;
        }
        if ($this->download_expires_at && now()->gt($this->download_expires_at)) {
            return false;
        }
        if ($this->download_count >= 10) {
            return false;
        }
        return true;
    }
}
