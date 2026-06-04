<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class AuthorService
{
    /**
     * Credit royalti author ke balance_pending + total_earned saat order PAID.
     * Idempotent — kedua controller (PayPal & Midtrans) sudah guard double-paid,
     * tapi kita dobel-check via author_earning_credited flag di order.
     */
    public function recordEarning(Order $order): void
    {
        if (($order->author_earning ?? 0) <= 0) {
            return;
        }

        // Idempotency: kalau sudah di-credit, skip
        if ($order->author_earning_credited) {
            return;
        }

        $author = $order->book?->author;
        if (! $author) {
            Log::warning('[Author] recordEarning: author not found', ['order' => $order->order_code]);
            return;
        }

        // Credit ke balance pending (cooling 7 hari — akan dipindah ke available oleh scheduler)
        $author->increment('balance_pending', $order->author_earning);
        $author->increment('total_earned', $order->author_earning);

        // Flag agar tidak double-credit
        $order->update(['author_earning_credited' => true]);

        Log::info('[Author] Royalti dikreditkan', [
            'order' => $order->order_code,
            'author_id' => $author->id,
            'amount' => $order->author_earning,
        ]);
    }

    /**
     * Rollback royalti kalau order refund/failed (hanya untuk pending earning).
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

        // Kurangi balance_pending saja (kalau sudah pindah ke available, biarkan — recoup manual)
        $pendingRollback = min($order->author_earning, $author->balance_pending);
        if ($pendingRollback > 0) {
            $author->decrement('balance_pending', $pendingRollback);
        }
        $author->decrement('total_earned', $order->author_earning);

        $order->update(['author_earning_credited' => false]);

        Log::info('[Author] Royalti di-rollback', [
            'order' => $order->order_code,
            'author_id' => $author->id,
            'amount' => $order->author_earning,
        ]);
    }
}
