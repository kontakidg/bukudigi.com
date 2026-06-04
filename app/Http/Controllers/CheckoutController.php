<?php

namespace App\Http\Controllers;

use App\Jobs\WatermarkEpubJob;
use App\Jobs\WatermarkPdfJob;
use App\Mail\OrderPaidMail;
use App\Models\Book;
use App\Models\Order;
use App\Services\AffiliateService;
use App\Services\MidtransService;
use App\Services\PaypalService;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function start(Request $request, Book $book, MidtransService $midtrans, VoucherService $voucherService, AffiliateService $affiliateService): RedirectResponse|View
    {
        abort_unless($book->status === 'active', 404);

        $user = $request->user();

        // Cek apakah user sudah pernah beli buku ini (active order)
        $existing = Order::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->whereIn('status', ['paid', 'watermarking', 'ready'])
            ->latest()
            ->first();

        if ($existing) {
            return redirect()->route('library')
                ->with('status', 'Kamu sudah membeli buku ini sebelumnya.');
        }

        $gross = (int) $book->price;
        $voucherDiscount = 0;
        $voucherId = null;
        $voucherCode = strtoupper(trim((string) $request->input('voucher_code', '')));
        $voucherInfo = null;

        // Validate voucher kalau dikirim
        if ($voucherCode !== '') {
            $result = $voucherService->validate($voucherCode, $user, $book, $gross);
            if ($result['valid']) {
                $voucherDiscount = $result['discount'];
                $voucherId = $result['voucher']->id;
                $voucherInfo = [
                    'code' => $result['voucher']->code,
                    'name' => $result['voucher']->name,
                    'discount' => $voucherDiscount,
                ];
            } else {
                return back()
                    ->withErrors(['voucher_code' => $result['reason']])
                    ->withInput();
            }
        }

        $netAmount = $gross - $voucherDiscount;

        // Affiliate attribution dari cookie (kalau ada & valid & bukan diri sendiri)
        $resolved = $affiliateService->resolveFromRequest($request, $user->id);
        $affiliate = $resolved['affiliate'] ?? null;
        $affiliateCode = $resolved['code'] ?? null;

        // Hitung split komisi:
        // - Tanpa affiliate: platform 20%, author 80%
        // - Dengan affiliate: platform 20%, affiliate 10% (dari net), author 70%
        $split = $affiliateService->computeSplit($netAmount, $affiliate);

        $order = DB::transaction(function () use ($user, $book, $gross, $voucherId, $voucherDiscount, $netAmount, $split, $affiliate, $affiliateCode) {
            return Order::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'author_id' => $book->author_id,
                'voucher_id' => $voucherId,
                'voucher_discount' => $voucherDiscount,
                'affiliate_id' => $affiliate?->id,
                'affiliate_code' => $affiliateCode?->code,
                'affiliate_code_id' => $affiliateCode?->id,
                'affiliate_commission' => $split['affiliate_commission'],
                'gross_amount' => $gross,
                'net_amount' => $netAmount,
                'commission' => $split['commission'],
                'author_earning' => $split['author_earning'],
                'gateway_fee' => 0,
                'status' => 'pending',
            ]);
        });

        // Tentukan gateway aktif: paypal | midtrans | stub
        $gateway = config('services.payment_gateway', 'stub');
        $paypalService = app(PaypalService::class);
        $snap = null;
        $paypal = null;

        if ($gateway === 'paypal' && $paypalService->isActive()) {
            $payable  = $netAmount;
            $rateInfo = $paypalService->liveRate();
            $usd      = round($payable / $rateInfo['rate'], 2);
            $paypal = [
                'mode'        => 'live',
                'sdk_url'     => $paypalService->sdkScriptUrl(),
                'amount_idr'  => $payable,
                'amount_usd'  => $usd,
                'rate'        => $rateInfo['rate'],
                'rate_source' => $rateInfo['source'],
                'rate_updated'=> $rateInfo['updated_at'],
                'rate_age'    => $rateInfo['age_minutes'],
                'create_url'  => route('paypal.create', $order->order_code),
                'capture_url' => route('paypal.capture', $order->order_code),
            ];
        } elseif ($gateway === 'midtrans') {
            $snap = $midtrans->createSnapToken($order);
        } else {
            // stub mode (promo gratis)
            $snap = $midtrans->createSnapToken($order);
        }

        return view('checkout.confirm', [
            'order' => $order->fresh()->load('book.author', 'voucher'),
            'snap' => $snap,
            'paypal' => $paypal,
            'gateway' => $gateway,
            'voucherInfo' => $voucherInfo,
        ]);
    }

    /**
     * AJAX endpoint untuk preview voucher tanpa create order.
     * Dipakai di tombol "Cek Voucher" sebelum klik Beli.
     */
    public function previewVoucher(Request $request, Book $book, VoucherService $voucherService): JsonResponse
    {
        abort_unless($book->status === 'active', 404);

        $code = strtoupper(trim((string) $request->input('code', '')));
        if ($code === '') {
            return response()->json(['valid' => false, 'reason' => 'Masukin kode voucher dulu.']);
        }

        $result = $voucherService->validate($code, $request->user(), $book, (int) $book->price);

        if (! $result['valid']) {
            return response()->json(['valid' => false, 'reason' => $result['reason']]);
        }

        return response()->json([
            'valid' => true,
            'code' => $result['voucher']->code,
            'name' => $result['voucher']->name,
            'discount' => $result['discount'],
            'discount_display' => 'Rp '.number_format($result['discount'], 0, ',', '.'),
            'net' => $result['net'],
            'net_display' => 'Rp '.number_format($result['net'], 0, ',', '.'),
        ]);
    }

    /**
     * Stub callback — simulasi pembayaran sukses tanpa lewat Midtrans beneran.
     * Di production, ini akan diganti dengan /api/midtrans/webhook yang verify signature.
     */
    public function stubPay(Request $request, string $orderCode, VoucherService $voucherService, AffiliateService $affiliateService): RedirectResponse
    {
        abort_unless(app(MidtransService::class)->isStub(), 404);

        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->status === 'pending') {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => 'STUB',
                'midtrans_order_id' => 'STUB-' . $order->order_code,
            ]);

            // Record voucher usage (kalau ada)
            $voucherService->recordUsage($order);

            // Record affiliate earning (kalau order pakai affiliate)
            $affiliateService->recordEarning($order->fresh());

            // Increment book sales counter (pakai net amount untuk revenue tracking)
            $order->book->increment('sales_count');
            $order->book->increment('total_revenue', $order->net_amount ?: $order->gross_amount);

            // Email konfirmasi pembayaran + link download
            try {
                $order->load('book.author', 'book.penName', 'user');
                Mail::to($order->user->email)->queue(new OrderPaidMail($order));
            } catch (\Throwable $e) {
                Log::warning('OrderPaidMail queue failed: '.$e->getMessage());
            }
        }

        // Dispatch watermark kalau belum selesai (status paid/watermarking = belum ready).
        // Pakai dispatchSync agar job jalan langsung, bukan via destructor PendingDispatch.
        if (in_array($order->fresh()->status, ['paid', 'watermarking'])) {
            try {
                WatermarkPdfJob::dispatchSync($order->id);

                if ($order->book->epub_master_path) {
                    WatermarkEpubJob::dispatchSync($order->id);
                }
            } catch (\Throwable $e) {
                Log::error('Watermark sync failed, using fallback', [
                    'order' => $order->order_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('library')
            ->with('status', "Pembayaran berhasil! Order #{$order->order_code} sedang diproses.");
    }
}
