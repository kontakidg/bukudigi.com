<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Support\Facades\DB;

/**
 * Business logic untuk validate + apply voucher.
 * Semua reason error pakai bahasa Indonesia (user-facing).
 */
class VoucherService
{
    /**
     * Validate voucher code untuk user + book + amount.
     *
     * Return:
     *   ['valid' => true, 'voucher' => Voucher, 'discount' => int (Rp), 'net' => int (Rp)]
     *   ['valid' => false, 'reason' => string]
     */
    public function validate(string $code, User $user, Book $book, int $grossAmount): array
    {
        $code = strtoupper(trim($code));

        $voucher = Voucher::where('code', $code)->first();
        if (! $voucher) {
            return ['valid' => false, 'reason' => 'Kode voucher tidak ditemukan.'];
        }

        if (! $voucher->is_active) {
            return ['valid' => false, 'reason' => 'Voucher sudah tidak aktif.'];
        }

        if (! $voucher->isWithinValidPeriod()) {
            $now = now();
            if ($voucher->valid_from && $now->lt($voucher->valid_from)) {
                return ['valid' => false, 'reason' => 'Voucher belum berlaku. Mulai berlaku '.$voucher->valid_from->format('d M Y').'.'];
            }
            return ['valid' => false, 'reason' => 'Voucher sudah expired.'];
        }

        if (! $voucher->hasRemainingQuota()) {
            return ['valid' => false, 'reason' => 'Kuota voucher sudah habis.'];
        }

        if (! $voucher->canBeUsedByUser($user->id)) {
            return ['valid' => false, 'reason' => 'Kamu sudah pernah pakai voucher ini.'];
        }

        if (! $voucher->appliesToBook($book)) {
            return ['valid' => false, 'reason' => 'Voucher tidak berlaku untuk buku ini.'];
        }

        if ($voucher->min_purchase_amount && $grossAmount < $voucher->min_purchase_amount) {
            $min = number_format($voucher->min_purchase_amount, 0, ',', '.');
            return ['valid' => false, 'reason' => "Minimum pembelian Rp {$min} untuk voucher ini."];
        }

        $discount = $voucher->calculateDiscount($grossAmount);
        $net = $grossAmount - $discount;

        return [
            'valid' => true,
            'voucher' => $voucher,
            'discount' => $discount,
            'net' => $net,
        ];
    }

    /**
     * Apply voucher ke order yang sudah ada (atau saat creation).
     * Tidak commit voucher_id ke order — caller harus pass voucher info ke Order::create.
     *
     * Method ini cuma untuk record usage + increment counter.
     * Dipanggil saat order CONFIRMED paid (bukan saat pending).
     */
    public function recordUsage(Order $order): void
    {
        if (! $order->voucher_id || ! $order->voucher_discount) {
            return;
        }

        DB::transaction(function () use ($order) {
            VoucherUsage::firstOrCreate(
                ['voucher_id' => $order->voucher_id, 'order_id' => $order->id],
                [
                    'user_id' => $order->user_id,
                    'discount_applied' => $order->voucher_discount,
                    'used_at' => now(),
                ]
            );

            Voucher::where('id', $order->voucher_id)->increment('used_count');
        });
    }

    /**
     * Rollback usage (saat order cancelled/refunded sebelum paid).
     */
    public function rollbackUsage(Order $order): void
    {
        if (! $order->voucher_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $deleted = VoucherUsage::where('voucher_id', $order->voucher_id)
                ->where('order_id', $order->id)
                ->delete();
            if ($deleted) {
                Voucher::where('id', $order->voucher_id)->decrement('used_count');
            }
        });
    }
}
