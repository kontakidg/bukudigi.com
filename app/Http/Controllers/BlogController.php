<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $category = trim((string) $request->input('kategori', ''));

        $query = Article::published()->latest('published_at');

        if ($category !== '') {
            $query->where('category', $category);
        }

        $articles = $query->paginate(12)->withQueryString();

        // Daftar kategori untuk filter
        $categories = Article::published()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('public.blog-index', compact('articles', 'categories', 'category'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->status === 'published'
            && (! $article->published_at || $article->published_at->isPast()), 404);

        // Increment views (tanpa update timestamps)
        $article->incrementQuietly('views_count');

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->when($article->category, fn ($q) => $q->where('category', $article->category))
            ->latest('published_at')
            ->take(3)
            ->get();

        // Buku relevan berdasarkan kategori artikel
        $relevantBooks = \App\Models\Book::active()
            ->when($article->category, fn ($q) => $q->whereHas('category',
                fn ($c) => $c->where('name', $article->category)
            ))
            ->orderBy('rating_avg', 'desc')
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get();

        return view('public.blog-show', compact('article', 'related', 'relevantBooks'));
    }
}
