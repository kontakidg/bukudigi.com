<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function show(Request $request, string $orderCode): BinaryFileResponse
    {
        $order = Order::with('book')
            ->where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $order->canDownload()) {
            abort(403, 'Download link sudah expired atau quota habis.');
        }

        if (! $order->watermarked_pdf_path) {
            abort(409, 'PDF belum siap. Coba lagi beberapa detik lagi.');
        }

        $abs = Storage::disk('local')->path($order->watermarked_pdf_path);
        if (! is_file($abs)) {
            abort(404, 'File watermarked tidak ditemukan.');
        }

        $order->increment('download_count');
        DownloadLog::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'downloaded_at' => now(),
        ]);

        $filename = preg_replace('/[^a-z0-9-]+/i', '-', $order->book->slug ?? 'ebook') . '.pdf';

        return response()->file($abs, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
