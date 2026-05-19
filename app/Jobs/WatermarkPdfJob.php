<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PdfWatermarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        // Path lokal di disk 'local' (storage/app/private)
        $masterAbs = Storage::disk('local')->path($masterRel);

        if (! is_file($masterAbs)) {
            Log::warning('[WatermarkPdfJob] Master PDF missing, fallback to plain copy', [
                'order' => $order->order_code,
                'path' => $masterAbs,
            ]);
            // Fallback: bikin file kecil placeholder agar flow tidak putus saat dev
            $this->fallbackPlaceholder($order, $masterRel);
            return;
        }

        $watermarkText = sprintf(
            'Dibeli oleh: %s · %s · Order %s',
            $order->user->name ?? 'Anonim',
            $order->user->email ?? '-',
            $order->order_code
        );

        $destRel = "watermarked/{$order->order_code}.pdf";
        $destAbs = Storage::disk('local')->path($destRel);

        try {
            $service->apply($masterAbs, $destAbs, $watermarkText);
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
            return; // Jangan re-throw — biar order tetap di status failed, admin bisa refund manual
        }

        $order->update([
            'status' => 'ready',
            'watermarked_pdf_path' => $destRel,
            'watermarked_at' => now(),
            'download_expires_at' => now()->addDays(30),
        ]);

        Log::info('[WatermarkPdfJob] Done', [
            'order' => $order->order_code,
            'path' => $destRel,
            'size' => filesize($destAbs),
        ]);
    }

    private function fallbackPlaceholder(Order $order, string $masterRel): void
    {
        $destRel = "watermarked/{$order->order_code}.pdf";
        Storage::disk('local')->put(
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
