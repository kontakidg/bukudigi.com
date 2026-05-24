<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateEarning extends Model
{
    protected $fillable = [
        'affiliate_id',
        'order_id',
        'amount',
        'rate_snapshot',
        'status',
        'available_at',
        'paid_out_at',
        'payout_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'rate_snapshot' => 'decimal:2',
            'available_at' => 'datetime',
            'paid_out_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class);
    }
}
