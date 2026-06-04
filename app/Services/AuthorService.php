<?php

namespace App\Services;

use App\Models\Author;
use App\Models\AuthorPayout;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class AuthorService
{
    public const COOLING_DAYS    = 7;
    public const PAYOUT_THRESHOLD = 100_000;   // Rp
    public const TRANSFER_FEE    = 3_250;       // 50:50 dari Rp 6.500

    /**
     * Credit royalti author ke balance_pending + total_earned saat order PAID.
     */
    public function recordEarning(Order $order): void
    {
        if (($order->author_earning ?? 0) <= 0) {
            return;
        }

        if ($order->author_earning_credited) {
            return;
        }

        $author = $order->book?->author;
        if (! $author) {
            Log::warning('[Author] recordEarning: author not found', ['order' => $order->order_code]);
            return;
        }

        $author->increment('balance_pending', $order->author_earning);
        $author->increment('total_earned', $order->author_earning);
        $order->update(['author_earning_credited' => true]);

        Log::info('[Author] Royalti dikreditkan', [
            'order'     => $order->order_code,
            'author_id' => $author->id,
            'amount'    => $order->author_earning,
        ]);
    }

    /**
     * Rollback royalti kalau order refund/failed.
     */
    public function cancelEarning(Order $order): void
    {
        if (! $order->author_earning_credited) {
            return;
        }

        $author = $order->book?->author;
        if (! $author) {
            return;
        }

        $pendingRollback = min($order->author_earning, $author->balance_pending);
        if ($pendingRollback > 0) {
            $author->decrement('balance_pending', $pendingRollback);
        }
        $author->decrement('total_earned', $order->author_earning);
        $order->update(['author_earning_credited' => false]);

        Log::info('[Author] Royalti di-rollback', [
            'order'     => $order->order_code,
            'author_id' => $author->id,
            'amount'    => $order->author_earning,
        ]);
    }

    /**
     * Pindahkan earning pending → available kalau cooling 7 hari sudah lewat.
     * Dipanggil dari scheduler harian (mirip AffiliateService).
     */
    public function maturePendingEarnings(): int
    {
        $cutoff = now()->subDays(self::COOLING_DAYS);

        // Cari order yang earning sudah dikreditkan, paid_at lewat cooling, tapi masih di pending
        $orders = Order::whereIn('status', ['paid', 'watermarking', 'ready'])
            ->where('author_earning_credited', true)
            ->where('author_earning', '>', 0)
            ->whereNotNull('paid_at')
            ->where('paid_at', '<=', $cutoff)
            ->whereDoesntHave('book', fn ($q) => $q->whereNull('author_id')) // punya author
            ->with('book.author')
            ->get();

        // Untuk setiap author, akumulasi total yang siap dipindah
        $byAuthor = [];
        foreach ($orders as $order) {
            $authorId = $order->book->author_id ?? null;
            if (! $authorId) {
                continue;
            }
            $byAuthor[$authorId] = ($byAuthor[$authorId] ?? 0) + $order->author_earning;
        }

        $count = 0;
        foreach ($byAuthor as $authorId => $amount) {
            $author = Author::find($authorId);
            if (! $author) {
                continue;
            }
            // Kurangi pending, tambah available (max sampai balance_pending yg ada)
            $move = min($amount, $author->balance_pending);
            if ($move > 0) {
                $author->decrement('balance_pending', $move);
                $author->increment('balance_available', $move);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Hitung rincian payout untuk seorang author.
     * Return array siap dipakai untuk form request & slip cetak.
     */
    public function calcPayout(Author $author): array
    {
        $gross   = $author->balance_available;
        $rate    = $author->hasNpwp() ? 2 : 4;
        $pph23   = (int) round($gross * $rate / 100);
        $fee     = self::TRANSFER_FEE;
        $net     = $gross - $pph23 - $fee;

        return [
            'gross'       => $gross,
            'pph23_rate'  => $rate,
            'pph23_amount'=> $pph23,
            'transfer_fee'=> $fee,
            'net'         => max(0, $net),
            'can_payout'  => $gross >= self::PAYOUT_THRESHOLD,
            'threshold'   => self::PAYOUT_THRESHOLD,
        ];
    }

    /**
     * Buat payout request dari author.
     */
    public function requestPayout(Author $author): AuthorPayout
    {
        $calc = $this->calcPayout($author);

        if (! $calc['can_payout']) {
            throw new \RuntimeException('Saldo tersedia di bawah threshold minimum Rp '.number_format(self::PAYOUT_THRESHOLD, 0, ',', '.'));
        }

        // Cek tidak ada request aktif
        $existing = AuthorPayout::where('author_id', $author->id)
            ->whereIn('status', ['requested', 'processing'])
            ->exists();

        if ($existing) {
            throw new \RuntimeException('Kamu sudah punya permintaan payout yang sedang diproses. Tunggu hingga selesai.');
        }

        return AuthorPayout::create([
            'author_id'    => $author->id,
            'period'       => now()->format('Y-m'),
            'gross_earning'=> $calc['gross'],
            'pph23_rate'   => $calc['pph23_rate'],
            'pph23_amount' => $calc['pph23_amount'],
            'transfer_fee' => $calc['transfer_fee'],
            'net_transfer' => $calc['net'],
            'bank_name'    => $author->bank_name,
            'bank_account' => $author->bank_account,
            'bank_holder'  => $author->bank_holder,
            'npwp'         => $author->npwp,
            'status'       => 'requested',
            'requested_at' => now(),
        ]);
    }
}
