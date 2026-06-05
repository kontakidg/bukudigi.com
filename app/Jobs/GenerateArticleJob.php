<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\AnthropicService;
use App\Services\ArticleImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;   // hindari dobel-charge token AI saat retry
    public int $timeout = 180; // beri ruang untuk polling gambar KIE

    public function __construct(
        public int $articleId,
        public string $topic,
        public int $words = 800,
        public string $imageStyle = 'ilustrasi',
    ) {}

    public function handle(AnthropicService $ai, ArticleImageService $img): void
    {
        $article = Article::find($this->articleId);
        if (! $article || $article->ai_status !== 'pending') {
            return;
        }

        $article->update(['ai_status' => 'generating', 'ai_error' => null]);

        try {
            // 1. Teks artikel (Claude)
            $result = $ai->generateArticle($article->title, $this->topic, $this->words);

            $article->content = $result['content_html'];
            $article->excerpt = $result['excerpt'] ?: $article->excerpt;
            if (! empty($result['meta_description'])) {
                $article->meta_description = $result['meta_description'];
            }
            $article->save();

            // 2. Gambar (KIE Nano Banana) + watermark — best effort
            try {
                $prompt = $img->imagePrompt($article->title, $this->topic, $this->imageStyle);
                $cover = $img->generateAndStore($prompt, $article->slug);
                if ($cover) {
                    $article->update(['cover_path' => $cover]);
                } else {
                    Log::info('[GenerateArticle] gambar tidak dihasilkan (lanjut tanpa cover)', [
                        'article' => $article->slug,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('[GenerateArticle] gambar gagal', [
                    'article' => $article->slug,
                    'error'   => $e->getMessage(),
                ]);
            }

            // 3. Tandai selesai + publikasi
            $article->update(['ai_status' => 'done', 'ai_generated' => true]);

            $publishAt = $article->published_at;
            if (! $publishAt || $publishAt->isPast()) {
                // Terbit sekarang + auto-post FB
                $article->update(['status' => 'published']);
                PostArticleToFacebookJob::dispatch($article->id);
            } else {
                // Terjadwal — scheduler (articles:publish-scheduled) yang publish + FB
                $article->update(['status' => 'scheduled']);
            }
        } catch (\Throwable $e) {
            $article->update([
                'ai_status' => 'failed',
                'ai_error'  => mb_substr($e->getMessage(), 0, 1000),
            ]);
            Log::error('[GenerateArticle] gagal', [
                'article' => $article->slug ?? $this->articleId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        if ($article = Article::find($this->articleId)) {
            $article->update([
                'ai_status' => 'failed',
                'ai_error'  => mb_substr($e->getMessage(), 0, 1000),
            ]);
        }
    }
}
