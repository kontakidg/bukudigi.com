<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\EpubWatermarkService;
use App\Support\PrivateStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
            return;
        }

        if (! PrivateStorage::exists($masterRel)) {
            Log::warning('[WatermarkEpubJob] EPUB master missing', [
                'order' => $order->order_code,
                'path' => $masterRel,
            ]);
            return;
        }

        $masterLocal = PrivateStorage::localPath($masterRel);
        $tempDest = tempnam(sys_get_temp_dir(), 'wmepub-');
        $destRel = "watermarked-epub/{$order->order_code}.epub";

        try {
            $service->apply($masterLocal['path'], $tempDest, [
                'name' => $order->user->name ?? 'Anonim',
                'email' => $order->user->email ?? '-',
                'order_code' => $order->order_code,
            ]);
            PrivateStorage::putFromLocal($tempDest, $destRel);
        } catch (Throwable $e) {
            Log::error('[WatermarkEpubJob] Failed', [
                'order' => $order->order_code,
                'error' => $e->getMessage(),
            ]);
            PrivateStorage::cleanup($masterLocal);
            @unlink($tempDest);
            return;
        }

        $size = is_file($tempDest) ? filesize($tempDest) : 0;
        PrivateStorage::cleanup($masterLocal);
        @unlink($tempDest);

        $order->update(['watermarked_epub_path' => $destRel]);

        Log::info('[WatermarkEpubJob] Done', [
            'order' => $order->order_code,
            'path' => $destRel,
            'size' => $size,
        ]);
    }
}
