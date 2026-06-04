<?php

namespace App\Http\Controllers;

use App\Jobs\WatermarkEpubJob;
use App\Jobs\WatermarkPdfJob;
use App\Mail\AuthorBookSoldMail;
use App\Mail\OrderPaidMail;
use App\Models\Order;
use App\Services\AffiliateService;
use App\Services\AuthorService;
use App\Services\MidtransService;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Endpoint Midtrans HTTP notification.
 * Midtrans akan POST ke URL ini setiap status transaksi berubah.
 *
 * Catatan keamanan: signature di-verify di MidtransService::parseNotification().
 * Endpoint dikecualikan dari CSRF (lihat bootstrap/app.php).
 */
class MidtransWebhookController extends Controller
{
    public function handle(Request $request, MidtransService $midtrans, VoucherService $voucherService, AffiliateService $affiliateService, AuthorService $authorService): JsonResponse
    {
        if ($midtrans->isStub()) {
            return response()->json(['message' => 'Midtrans mode = stub, webhook disabled'], 200);
        }

        // Detect test payload dari Midtrans dashboard "Send Test Notification".
        // Test payload ini punya signature dummy yang tidak match server key kita —
        // tapi Midtrans expect 200 untuk lulus health check endpoint.
        $rawOrderId = (string) $request->input('order_id', '');
        if (str_starts_with($rawOrderId, 'payment_notif_test_')) {
            Log::info('[Midtrans Webhook] Test notification received (skip processing)', [
                'order_id' => $rawOrderId,
            ]);
            return response()->json([
                'message' => 'Test notification acknowledged. Endpoint is reachable.',
            ], 200);
        }

        try {
            $notif = $midtrans->parseNotification();
        } catch (Throwable $e) {
            Log::warning('[Midtrans Webhook] Invalid signature/notification', [
                'error' => $e->getMessage(),
                'order_id' => $rawOrderId,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $orderCode = $notif['order_id'];
        $order = Order::with('book', 'user')->where('order_code', $orderCode)->first();

        if (! $order) {
            Log::warning('[Midtrans Webhook] Order not found', ['order_id' => $orderCode]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Verify gross_amount match (anti-tampering)
        $payable = $order->net_amount ?: $order->gross_amount;
        $notifAmount = (int) $notif['gross_amount'];
        if ($notifAmount !== (int) $payable) {
            Log::warning('[Midtrans Webhook] Amount mismatch', [
                'order' => $orderCode,
                'expected' => $payable,
                'received' => $notifAmount,
            ]);
            return response()->json(['message' => 'Amount mismatch'], 400);
        }

        $newStatus = $midtrans->mapTransactionStatus(
            $notif['transaction_status'],
            $notif['fraud_status']
        );

        Log::info('[Midtrans Webhook] Notification received', [
            'order' => $orderCode,
            'midtrans_status' => $notif['transaction_status'],
            'mapped_status' => $newStatus,
            'payment_type' => $notif['payment_type'],
        ]);

        // Idempotent — kalau order sudah ready/paid, skip processing ulang
        if (in_array($order->status, ['paid', 'watermarking', 'ready'], true) && $newStatus === 'paid') {
            return response()->json(['message' => 'Order already processed'], 200);
        }

        DB::transaction(function () use ($order, $notif, $newStatus, $voucherService, $midtrans, $affiliateService, $authorService) {
            $update = [
                'status' => $newStatus,
                'midtrans_order_id' => $notif['transaction_id'] ?: $order->order_code,
                'payment_method' => $notif['payment_type'],
                'midtrans_response' => $notif['raw'] instanceof \stdClass
                    ? json_decode(json_encode($notif['raw']), true)
                    : (array) $notif['raw'],
            ];

            if ($newStatus === 'paid' && ! $order->paid_at) {
                $update['paid_at'] = now();
            }

            $order->update($update);

            if ($newStatus === 'paid' && $order->wasChanged('status')) {
                $fresh = $order->fresh();

                // Record voucher usage
                $voucherService->recordUsage($fresh);

                // Record affiliate earning (no-op kalau order ga pakai affiliate)
                $affiliateService->recordEarning($fresh);

                // Author royalti → balance_pending
                $authorService->recordEarning($fresh);

                // Increment sales counter pakai net amount
                $order->book->increment('sales_count');
                $order->book->increment('total_revenue', $order->net_amount ?: $order->gross_amount);

                // Dispatch watermark jobs
                WatermarkPdfJob::dispatch($order->id);
                if ($order->book->epub_master_path) {
                    WatermarkEpubJob::dispatch($order->id);
                }

                // Email notif — pembeli
                try {
                    $order->load('book.author', 'book.penName', 'user');
                    Mail::to($order->user->email)->queue(new OrderPaidMail($order));
                } catch (Throwable $e) {
                    Log::warning('[Midtrans Webhook] OrderPaidMail queue failed: '.$e->getMessage());
                }

                // Email notif — author (buku terjual)
                try {
                    $authorUser = $order->book->author?->user;
                    if ($authorUser) {
                        Mail::to($authorUser->email)->queue(new AuthorBookSoldMail($order));
                    }
                } catch (Throwable $e) {
                    Log::warning('[Midtrans Webhook] AuthorBookSoldMail queue failed: '.$e->getMessage());
                }
            }

            if ($newStatus === 'failed') {
                $update['refund_reason'] = 'Pembayaran dibatalkan/expired: '.$notif['transaction_status'];
                $freshFailed = $order->fresh();
                // Voucher usage di-rollback (kalau memang udah ke-record sebelumnya)
                $voucherService->rollbackUsage($freshFailed);
                // Cancel affiliate earning kalau ada
                $affiliateService->cancelEarning($freshFailed);

                // Rollback author royalti
                $authorService->cancelEarning($freshFailed);
            }
        });

        return response()->json(['message' => 'OK'], 200);
    }
}
