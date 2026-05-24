<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherUsage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'voucher_id',
        'order_id',
        'user_id',
        'discount_applied',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_applied' => 'integer',
            'used_at' => 'datetime',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
