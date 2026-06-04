<?php

namespace App\Services;

use App\Jobs\PostArticleToFacebookJob;
use App\Models\Article;

class ArticleService
{
    /**
     * Publish artikel berstatus 'scheduled' yang published_at-nya sudah lewat.
     * Dipanggil dari scheduler (articles:publish-scheduled).
     * Return jumlah artikel yang dipublish.
     */
    public function publishDue(): int
    {
        $due = Article::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($due as $article) {
            $article->update(['status' => 'published']);
            PostArticleToFacebookJob::dispatch($article->id);
            $count++;
        }

        return $count;
    }
}
