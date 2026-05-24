<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\PdfPreviewService;
use App\Services\PdfToImageService;
use App\Support\PrivateStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GeneratePreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(public int $bookId)
    {
    }

    public function handle(PdfPreviewService $previewService, PdfToImageService $imageService): void
    {
        $book = Book::find($this->bookId);
        if (! $book || ! $book->pdf_master_path) {
            Log::info('[GeneratePreviewJob] Skip — book missing or no master', ['id' => $this->bookId]);
            return;
        }

        if (! PrivateStorage::disk()->exists($book->pdf_master_path)) {
            Log::warning('[GeneratePreviewJob] Master not found', ['book' => $book->id, 'path' => $book->pdf_master_path]);
            return;
        }

        $masterLocal = PrivateStorage::localPath($book->pdf_master_path);
        $masterAbs = $masterLocal['path'];

        $updates = [];

        // === 1. Preview PDF (5 halaman + watermark PREVIEW) — disimpan di disk public ===
        $previewRel = "book-previews/{$book->slug}/preview.pdf";
        $previewAbs = Storage::disk('public')->path($previewRel);

        // Pastikan dir target ada (disk 'public' = local)
        @mkdir(dirname($previewAbs), 0775, true);

        try {
            $previewService->generate($masterAbs, $previewAbs, $book->title, 5);
            $updates['preview_pdf_path'] = $previewRel;
        } catch (Throwable $e) {
            Log::error('[GeneratePreviewJob] PDF preview failed', [
                'book' => $book->id,
                'error' => $e->getMessage(),
            ]);
        }

        // === 2. PNG per halaman (render dari preview PDF agar sudah ber-watermark PREVIEW) ===
        if (isset($updates['preview_pdf_path']) && $imageService->isAvailable()) {
            $pngDirAbs = Storage::disk('public')->path("book-previews/{$book->slug}");

            try {
                $files = $imageService->render($previewAbs, $pngDirAbs, 'page', 5, 120);
                $relPaths = array_map(
                    fn ($abs) => "book-previews/{$book->slug}/" . basename($abs),
                    $files
                );
                $updates['preview_image_paths'] = $relPaths;
            } catch (Throwable $e) {
                Log::error('[GeneratePreviewJob] PNG render failed', [
                    'book' => $book->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif (! $imageService->isAvailable()) {
            Log::info('[GeneratePreviewJob] Ghostscript not available, skip PNG render');
        }

        PrivateStorage::cleanup($masterLocal);

        if (! empty($updates)) {
            $book->update($updates);
        }

        Log::info('[GeneratePreviewJob] Done', [
            'book' => $book->id,
            'pdf' => $updates['preview_pdf_path'] ?? null,
            'pngs' => isset($updates['preview_image_paths']) ? count($updates['preview_image_paths']) : 0,
        ]);
    }
}
