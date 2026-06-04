<?php

namespace App\Http\Controllers;

use App\Jobs\WatermarkEpubJob;
use App\Jobs\WatermarkPdfJob;
use App\Mail\OrderPaidMail;
use App\Models\Order;
use App\Services\AffiliateService;
use App\Services\PaypalService;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaypalController extends Controller
{
    /**
     * POST /api/paypal/create-order/{orderCode}
     * Frontend Smart Button → backend create PayPal order → return paypal_order_id.
     */
    public function createOrder(Request $request, string $orderCode, PaypalService $paypal): JsonResponse
    {
        if (! $paypal->isActive()) {
            return response()->json(['error' => 'PayPal belum aktif.'], 503);
        }

        $order = Order::with('book')
            ->where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $result = $paypal->createOrder($order);

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * POST /api/paypal/capture/{orderCode}
     * Frontend onApprove → backend capture → mark order paid + dispatch watermark.
     */
    public function capture(Request $request, string $orderCode, PaypalService $paypal, VoucherService $voucherService, AffiliateService $affiliateService): JsonResponse
    {
        $order = Order::with('book', 'user')
            ->where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Idempotent — kalau sudah paid, return success
        if (in_array($order->status, ['paid', 'watermarking', 'ready'], true)) {
            return response()->json(['success' => true, 'already_paid' => true]);
        }

        if (! $order->paypal_order_id) {
            return response()->json(['error' => 'Order belum dibuat di PayPal.'], 400);
        }

        $capture = $paypal->captureOrder($order->paypal_order_id);

        if ($capture['status'] !== 'COMPLETED') {
            Log::warning('[PayPal Capture] Non-completed status', [
                'order' => $orderCode,
                'paypal_status' => $capture['status'],
                'error' => $capture['error'] ?? null,
            ]);
            return response()->json([
                'success' => false,
                'status' => $capture['status'],
                'error' => $capture['error'] ?? 'Capture gagal.',
            ], 400);
        }

        // Capture sukses — update order + dispatch fulfillment
        DB::transaction(function () use ($order, $capture) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => 'paypal',
                'payment_gateway' => 'paypal',
                'paypal_capture_id' => $capture['capture_id'],
                'midtrans_response' => $capture['raw'] ?? null, // reuse field untuk store raw response
            ]);
        });

        $fresh = $order->fresh();

        // Voucher usage record
        $voucherService->recordUsage($fresh);

        // Affiliate earning record
        $affiliateService->recordEarning($fresh);

        // Increment sales counter (pakai net amount)
        $order->book->increment('sales_count');
        $order->book->increment('total_revenue', $order->net_amount ?: $order->gross_amount);

        // Dispatch watermark jobs (sync biar segera ready)
        try {
            WatermarkPdfJob::dispatchSync($order->id);
            if ($order->book->epub_master_path) {
                WatermarkEpubJob::dispatchSync($order->id);
            }
        } catch (Throwable $e) {
            Log::error('[PayPal] Watermark sync failed', [
                'order' => $order->order_code,
                'error' => $e->getMessage(),
            ]);
        }

        // Email notif
        try {
            $order->load('book.author', 'book.penName', 'user');
            Mail::to($order->user->email)->queue(new OrderPaidMail($order));
        } catch (Throwable $e) {
            Log::warning('[PayPal] OrderPaidMail queue failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'redirect' => route('library', ['paid' => $order->order_code]),
        ]);
    }
}
