<?php

namespace App\Http\Controllers;

use App\Jobs\WatermarkPdfJob;
use App\Models\Book;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function start(Request $request, Book $book, MidtransService $midtrans): RedirectResponse|View
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
        $commission = (int) round($gross * (env('PLATFORM_COMMISSION_PERCENT', 20) / 100));
        $authorEarning = $gross - $commission;

        $order = DB::transaction(function () use ($user, $book, $gross, $commission, $authorEarning) {
            return Order::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'author_id' => $book->author_id,
                'gross_amount' => $gross,
                'commission' => $commission,
                'author_earning' => $authorEarning,
                'gateway_fee' => 0,
                'status' => 'pending',
            ]);
        });

        $snap = $midtrans->createSnapToken($order);

        return view('checkout.confirm', [
            'order' => $order->fresh()->load('book.author'),
            'snap' => $snap,
        ]);
    }

    /**
     * Stub callback — simulasi pembayaran sukses tanpa lewat Midtrans beneran.
     * Di production, ini akan diganti dengan /api/midtrans/webhook yang verify signature.
     */
    public function stubPay(Request $request, string $orderCode): RedirectResponse
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

            // Increment book sales counter
            $order->book->increment('sales_count');
            $order->book->increment('total_revenue', $order->gross_amount);

            // Dispatch watermark job
            WatermarkPdfJob::dispatch($order->id);
        }

        return redirect()->route('library')
            ->with('status', "Pembayaran berhasil! Order #{$order->order_code} sedang diproses.");
    }
}
