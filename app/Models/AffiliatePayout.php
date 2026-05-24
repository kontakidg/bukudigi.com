<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatePayout extends Model
{
    protected $fillable = [
        'affiliate_id',
        'period',
        'gross_earning',
        'transfer_fee',
        'net_transfer',
        'status',
        'processed_at',
        'admin_note',
        'bank_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'gross_earning' => 'integer',
            'transfer_fee' => 'integer',
            'net_transfer' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(AffiliateEarning::class);
    }
}
