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

        if (! PrivateStorage::exists($order->watermarked_pdf_path)) {
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

        return $this->streamFile($order->watermarked_pdf_path, $filename, 'application/pdf', 'attachment');
    }

    /** Download EPUB watermarked. */
    public function epub(Request $request, string $orderCode): StreamedResponse
    {
        $order = $this->resolveOrderForEpub($request, $orderCode);
        if (! PrivateStorage::exists($order->watermarked_epub_path)) {
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

        return $this->streamFile($order->watermarked_epub_path, $filename, 'application/epub+zip', 'attachment');
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
        if (! PrivateStorage::exists($order->watermarked_epub_path)) {
            abort(404, 'File EPUB tidak ditemukan.');
        }

        return $this->streamFile($order->watermarked_epub_path, null, 'application/epub+zip', 'inline');
    }

    /**
     * Stream file dari private disk (works for both local and S3).
     * Pakai readStream + manual pipe karena Storage->download() bermasalah dengan S3 (R2).
     */
    private function streamFile(string $relPath, ?string $filename, string $contentType, string $disposition = 'attachment'): StreamedResponse
    {
        $size = PrivateStorage::size($relPath);

        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => (string) $size,
            'Cache-Control' => 'private, no-store',
        ];

        if ($filename) {
            $safeName = str_replace('"', '', $filename);
            $headers['Content-Disposition'] = "{$disposition}; filename=\"{$safeName}\"";
        } else {
            $headers['Content-Disposition'] = $disposition;
        }

        return Response::stream(function () use ($relPath) {
            $stream = PrivateStorage::disk()->readStream($relPath);
            if (is_resource($stream)) {
                while (! feof($stream)) {
                    echo fread($stream, 8192);
                    @ob_flush();
                    flush();
                }
                fclose($stream);
            }
        }, 200, $headers);
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
