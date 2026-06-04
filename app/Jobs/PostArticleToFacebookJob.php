<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\FacebookPageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostArticleToFacebookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public int $articleId) {}

    public function handle(FacebookPageService $fb): void
    {
        $article = Article::find($this->articleId);

        if (! $article || ! $article->isReadyToPost()) {
            return;
        }

        $postId = $fb->postArticle($article);

        if ($postId) {
            $article->update([
                'fb_post_id'   => $postId,
                'fb_posted_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Facebook] PostArticleToFacebookJob gagal', [
            'article_id' => $this->articleId,
            'error'      => $e->getMessage(),
        ]);
    }
}
