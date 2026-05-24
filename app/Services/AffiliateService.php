<?php

namespace App\Services;

use App\Http\Middleware\AffiliateTracker;
use App\Models\Affiliate;
use App\Models\AffiliateEarning;
use App\Models\Order;
use Illuminate\Http\Request;

class AffiliateService
{
    /**
     * Cooling period sebelum komisi affiliate "available" (sinkron dengan author).
     */
    public const COOLING_DAYS = 7;

    /**
     * Default rate kalau affiliate ga punya override.
     */
    public const DEFAULT_RATE = 10.0;

    /**
     * Ambil affiliate aktif dari cookie request.
     * Buyer & affiliate tidak boleh sama (no self-refer).
     */
    public function resolveFromRequest(Request $request, int $buyerUserId): ?Affiliate
    {
        $code = $request->cookie(AffiliateTracker::COOKIE_NAME);
        if (! $code) {
            return null;
        }

        $aff = Affiliate::where('code', strtoupper($code))
            ->where('status', 'approved')
            ->first();

        if (! $aff) {
            return null;
        }

        if ((int) $aff->user_id === $buyerUserId) {
            return null;
        }

        return $aff;
    }

    /**
     * Hitung komisi affiliate dari net amount transaksi.
     * Return integer rupiah (rounded).
     */
    public function calcCommission(int $netAmount, Affiliate $affiliate): int
    {
        $rate = (float) $affiliate->commission_rate ?: self::DEFAULT_RATE;
        return (int) round($netAmount * ($rate / 100));
    }

    /**
     * Bagi komisi platform jadi: platform_cut & affiliate_cut.
     * Author tetap dapat (net - platform_full_commission). Bagian affiliate diambil DARI platform commission.
     *
     * Karena keputusan bisnis: platform 20%, affiliate 10% (dipotong dari 20% platform jadi platform sisa 10%),
     * dan author tetap 70% dari net (sama dengan: net - 30%).
     *
     * Implementasi: kalau ada affiliate, total komisi (platform+affiliate) = 30% dari net.
     * `commission` field di orders = total komisi NON-author (platform + affiliate).
     * `affiliate_commission` = porsi affiliate saja. Author earning = net - commission.
     */
    public function computeSplit(int $netAmount, ?Affiliate $affiliate): array
    {
        $platformPct = (int) env('PLATFORM_COMMISSION_PERCENT', 20);

        if (! $affiliate) {
            $commission = (int) round($netAmount * ($platformPct / 100));
            return [
                'commission' => $commission,
                'affiliate_commission' => 0,
                'author_earning' => $netAmount - $commission,
            ];
        }

        $affCommission = $this->calcCommission($netAmount, $affiliate);
        $platformCommission = (int) round($netAmount * ($platformPct / 100));
        $totalCommission = $platformCommission + $affCommission;

        // Safety: kalau gabungan > net (kasus rate aneh), cap supaya author ga negatif
        if ($totalCommission > $netAmount) {
            $totalCommission = $netAmount;
            $affCommission = max(0, $totalCommission - $platformCommission);
        }

        return [
            'commission' => $totalCommission,
            'affiliate_commission' => $affCommission,
            'author_earning' => $netAmount - $totalCommission,
        ];
    }

    /**
     * Buat record AffiliateEarning saat order PAID.
     * Idempotent — kalau sudah ada earning untuk order ini, skip.
     */
    public function recordEarning(Order $order): ?AffiliateEarning
    {
        if (! $order->affiliate_id || $order->affiliate_commission <= 0) {
            return null;
        }

        $existing = AffiliateEarning::where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $affiliate = Affiliate::find($order->affiliate_id);
        if (! $affiliate) {
            return null;
        }

        $availableAt = ($order->paid_at ?: now())->copy()->addDays(self::COOLING_DAYS);

        $earning = AffiliateEarning::create([
            'affiliate_id' => $affiliate->id,
            'order_id' => $order->id,
            'amount' => $order->affiliate_commission,
            'rate_snapshot' => $affiliate->commission_rate,
            'status' => 'pending',
            'available_at' => $availableAt,
        ]);

        // Update saldo pending & counter konversi
        $affiliate->increment('balance_pending', $order->affiliate_commission);
        $affiliate->increment('total_earned', $order->affiliate_commission);
        $affiliate->increment('conversions_count');

        return $earning;
    }

    /**
     * Cancel earning kalau order refunded/failed.
     */
    public function cancelEarning(Order $order): void
    {
        $earning = AffiliateEarning::where('order_id', $order->id)->first();
        if (! $earning || $earning->status === 'cancelled') {
            return;
        }

        $affiliate = $earning->affiliate;

        if ($earning->status === 'pending') {
            $affiliate?->decrement('balance_pending', $earning->amount);
        } elseif ($earning->status === 'available') {
            $affiliate?->decrement('balance_available', $earning->amount);
        }
        // kalau sudah 'paid' biarkan saja — recoup manual lewat admin

        if ($affiliate) {
            $affiliate->decrement('total_earned', $earning->amount);
            $affiliate->decrement('conversions_count');
        }

        $earning->update(['status' => 'cancelled']);
    }

    /**
     * Pindah earning pending → available kalau available_at sudah lewat.
     * Dipanggil dari scheduler harian.
     */
    public function maturePendingEarnings(): int
    {
        $matured = AffiliateEarning::where('status', 'pending')
            ->whereNotNull('available_at')
            ->where('available_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($matured as $earning) {
            $earning->update(['status' => 'available']);
            $aff = $earning->affiliate;
            if ($aff) {
                $aff->decrement('balance_pending', $earning->amount);
                $aff->increment('balance_available', $earning->amount);
            }
            $count++;
        }

        return $count;
    }
}
