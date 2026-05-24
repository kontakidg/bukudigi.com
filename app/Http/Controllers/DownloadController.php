<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Order;
use App\Support\PrivateStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function show(Request $request, string $orderCode): StreamedResponse
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

        if (! PrivateStorage::disk()->exists($order->watermarked_pdf_path)) {
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

        return PrivateStorage::disk()->download($order->watermarked_pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** Download EPUB watermarked. */
    public function epub(Request $request, string $orderCode): StreamedResponse
    {
        $order = $this->resolveOrderForEpub($request, $orderCode);
        if (! PrivateStorage::disk()->exists($order->watermarked_epub_path)) {
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

        return PrivateStorage::disk()->download($order->watermarked_epub_path, $filename, [
            'Content-Type' => 'application/epub+zip',
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
    public function streamEpub(Request $request, string $orderCode): StreamedResponse
    {
        $order = $this->resolveOrderForEpub($request, $orderCode);
        if (! PrivateStorage::disk()->exists($order->watermarked_epub_path)) {
            abort(404, 'File EPUB tidak ditemukan.');
        }

        return Response::stream(function () use ($order) {
            $stream = PrivateStorage::disk()->readStream($order->watermarked_epub_path);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
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
