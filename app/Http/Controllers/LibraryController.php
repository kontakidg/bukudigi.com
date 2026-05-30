<?php

namespace App\Http\Controllers;

use App\Jobs\WatermarkEpubJob;
use App\Jobs\WatermarkPdfJob;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'paid', 'watermarking', 'ready'])
            ->with(['book.author'])
            ->latest('created_at')
            ->paginate(20);

        return view('public.library', compact('orders'));
    }

    public function retry(Request $request, string $orderCode): RedirectResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['paid', 'watermarking'])
            ->firstOrFail();

        try {
            WatermarkPdfJob::dispatchSync($order->id);

            if ($order->book->epub_master_path) {
                WatermarkEpubJob::dispatchSync($order->id);
            }
        } catch (\Throwable $e) {
            Log::error('Watermark retry failed', [
                'order' => $order->order_code,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('library');
    }
}
