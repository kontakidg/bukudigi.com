<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorPayout extends Model
{
    protected $fillable = [
        'author_id',
        'period',
        'gross_earning',
        'pph23_rate',
        'pph23_amount',
        'transfer_fee',
        'net_transfer',
        'bank_name',
        'bank_account',
        'bank_holder',
        'npwp',
        'status',
        'requested_at',
        'processed_at',
        'bank_ref',
        'admin_note',
        'bank_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'gross_earning'  => 'integer',
            'pph23_rate'     => 'integer',
            'pph23_amount'   => 'integer',
            'transfer_fee'   => 'integer',
            'net_transfer'   => 'integer',
            'requested_at'   => 'datetime',
            'processed_at'   => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /** Label status untuk tampilan */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'requested'  => 'Menunggu Proses',
            'processing' => 'Sedang Diproses',
            'processed'  => 'Sudah Ditransfer',
            'failed'     => 'Gagal',
            default      => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'requested'  => 'amber',
            'processing' => 'blue',
            'processed'  => 'green',
            'failed'     => 'red',
            default      => 'gray',
        };
    }
}
