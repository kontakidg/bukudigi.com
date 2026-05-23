<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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

    /** Download EPUB watermarked. */
    public function epub(Request $request, string $orderCode): BinaryFileResponse
    {
        $order = $this->resolveOrderForEpub($request, $orderCode);
        $abs = Storage::disk('local')->path($order->watermarked_epub_path);
        if (! is_file($abs)) {
            abort(404, 'File EPUB tidak ditemukan.');
        }

        $order->increment('download_count');
        DownloadLog::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'downloaded_at' => now(),
        ]);

        $filename = preg_replace('/[^a-z0-9-]+/i', '-', $order->book->slug ?? 'ebook') . '.epub';

        return response()->file($abs, [
            'Content-Type' => 'application/epub+zip',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /** Halaman reader EPUB inline (pakai epub.js). */
    public function readEpub(Request $request, string $orderCode): View
    {
        $order = $this->resolveOrderForEpub($request, $orderCode);

        return view('public.epub-reader', [
            'order' => $order,
            'epubStreamUrl' => route('download.epub.stream', $order->order_code),
        ]);
    }

    /** Stream EPUB inline (untuk epub.js fetch). */
    public function streamEpub(Request $request, string $orderCode): BinaryFileResponse
    {
        $order = $this->resolveOrderForEpub($request, $orderCode);
        $abs = Storage::disk('local')->path($order->watermarked_epub_path);
        if (! is_file($abs)) {
            abort(404, 'File EPUB tidak ditemukan.');
        }

        return response()->file($abs, [
            'Content-Type' => 'application/epub+zip',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Helper: validasi order & EPUB ready. */
    private function resolveOrderForEpub(Request $request, string $orderCode): Order
    {
        $order = Order::with('book')
            ->where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $order->canDownload()) {
            abort(403, 'Akses sudah expired atau quota habis.');
        }

        if (! $order->watermarked_epub_path) {
            abort(409, 'EPUB belum siap atau buku ini tidak memiliki versi EPUB.');
        }

        return $order;
    }
}
