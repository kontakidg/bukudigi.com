<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PdfWatermarkService;
use App\Support\PrivateStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class WatermarkPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $orderId)
    {
    }

    public function handle(PdfWatermarkService $service): void
    {
        $order = Order::with(['book', 'user'])->find($this->orderId);
        if (! $order || $order->status !== 'paid') {
            Log::info('[WatermarkPdfJob] Skip', ['order_id' => $this->orderId, 'status' => $order?->status]);
            return;
        }

        $order->update(['status' => 'watermarking']);

        $masterRel = $order->book->pdf_master_path;

        // Download master ke temp local (kalau R2). PrivateStorage handle keduanya.
        if (! PrivateStorage::exists($masterRel)) {
            Log::warning('[WatermarkPdfJob] Master PDF missing, fallback to plain copy', [
                'order' => $order->order_code,
                'path' => $masterRel,
            ]);
            $this->fallbackPlaceholder($order, $masterRel);
            return;
        }

        $masterLocal = PrivateStorage::localPath($masterRel);
        $watermarkText = sprintf(
            'Dibeli oleh: %s · %s · Order %s',
            $order->user->name ?? 'Anonim',
            $order->user->email ?? '-',
            $order->order_code
        );

        // Tulis hasil ke temp file dulu, lalu upload ke private storage
        $tempDest = tempnam(sys_get_temp_dir(), 'wmpdf-');
        $destRel = "watermarked/{$order->order_code}.pdf";

        try {
            $service->apply($masterLocal['path'], $tempDest, $watermarkText);
            PrivateStorage::putFromLocal($tempDest, $destRel);
        } catch (Throwable $e) {
            Log::error('[WatermarkPdfJob] Watermark failed', [
                'order' => $order->order_code,
                'master' => $masterRel,
                'error' => $e->getMessage(),
            ]);
            $order->update([
                'status' => 'failed',
                'refund_reason' => 'Watermark gagal: PDF master tidak valid. Refund otomatis.',
                'refunded_at' => now(),
            ]);
            PrivateStorage::cleanup($masterLocal);
            @unlink($tempDest);
            return;
        }

        $size = is_file($tempDest) ? filesize($tempDest) : 0;
        PrivateStorage::cleanup($masterLocal);
        @unlink($tempDest);

        $order->update([
            'status' => 'ready',
            'watermarked_pdf_path' => $destRel,
            'watermarked_at' => now(),
            'download_expires_at' => now()->addDays(30),
        ]);

        Log::info('[WatermarkPdfJob] Done', [
            'order' => $order->order_code,
            'path' => $destRel,
            'size' => $size,
        ]);
    }

    private function fallbackPlaceholder(Order $order, string $masterRel): void
    {
        $destRel = "watermarked/{$order->order_code}.pdf";
        PrivateStorage::disk()->put(
            $destRel,
            "%PDF-1.4\n% bukudigi placeholder (master tidak ditemukan: {$masterRel})\n% Order: {$order->order_code}\n%%EOF\n"
        );

        $order->update([
            'status' => 'ready',
            'watermarked_pdf_path' => $destRel,
            'watermarked_at' => now(),
            'download_expires_at' => now()->addDays(30),
        ]);
    }
}
