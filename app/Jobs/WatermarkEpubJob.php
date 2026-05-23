<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\EpubWatermarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WatermarkEpubJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public int $orderId)
    {
    }

    public function handle(EpubWatermarkService $service): void
    {
        $order = Order::with(['book', 'user'])->find($this->orderId);
        if (! $order) {
            return;
        }

        $masterRel = $order->book->epub_master_path;
        if (! $masterRel) {
            // Book tidak punya EPUB — skip silently
            return;
        }

        $masterAbs = Storage::disk('local')->path($masterRel);
        if (! is_file($masterAbs)) {
            Log::warning('[WatermarkEpubJob] EPUB master missing', [
                'order' => $order->order_code,
                'path' => $masterRel,
            ]);
            return;
        }

        $destRel = "watermarked-epub/{$order->order_code}.epub";
        $destAbs = Storage::disk('local')->path($destRel);

        try {
            $service->apply($masterAbs, $destAbs, [
                'name' => $order->user->name ?? 'Anonim',
                'email' => $order->user->email ?? '-',
                'order_code' => $order->order_code,
            ]);
        } catch (Throwable $e) {
            Log::error('[WatermarkEpubJob] Failed', [
                'order' => $order->order_code,
                'error' => $e->getMessage(),
            ]);
            // Don't fail order — PDF mungkin sudah ready. Cukup log.
            return;
        }

        $order->update(['watermarked_epub_path' => $destRel]);

        Log::info('[WatermarkEpubJob] Done', [
            'order' => $order->order_code,
            'path' => $destRel,
            'size' => filesize($destAbs),
        ]);
    }
}
