<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'newest');

        $books = Book::active()
            ->with(['author', 'penName', 'category'])
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('sales_count'))
            ->when($sort === 'price_low', fn ($q) => $q->orderBy('price'))
            ->when($sort === 'price_high', fn ($q) => $q->orderByDesc('price'))
            ->when($sort === 'newest' || ! in_array($sort, ['popular', 'price_low', 'price_high']),
                fn ($q) => $q->latest('approved_at'))
            ->paginate(24)
            ->withQueryString();

        return view('public.books-index', compact('books', 'sort'));
    }

    public function show(Request $request, Book $book)
    {
        abort_unless($book->status === 'active', 404);
        $book->load(['author', 'penName', 'category', 'tags']);

        // Track view (dedupe: 1 view per IP per book per hour)
        $cacheKey = sprintf('book-view:%d:%s', $book->id, sha1($request->ip() ?? 'anon'));
        if (! Cache::has($cacheKey)) {
            Cache::put($cacheKey, true, now()->addHour());
            BookView::create([
                'book_id' => $book->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'viewed_at' => now(),
            ]);
            $book->increment('views_count');
        }

        $related = Book::active()
            ->where('id', '!=', $book->id)
            ->where('category_id', $book->category_id)
            ->with(['author', 'penName'])
            ->take(5)
            ->get();

        // Reviews (visible) + cek eligibility user untuk review
        $reviews = $book->visibleReviews()->with('user')->get();

        $userReview = null;
        $canReview = false;
        if ($user = $request->user()) {
            $userReview = $book->reviews()->where('user_id', $user->id)->first();
            // Eligible kalau sudah beli (ada order paid/ready) — walau belum review
            $canReview = \App\Models\Order::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->whereIn('status', ['paid', 'watermarking', 'ready'])
                ->exists();
            // User adalah author buku ini? (untuk tampilkan form reply)
            $isBookAuthor = $user->author && $book->author_id === $user->author->id;
        } else {
            $isBookAuthor = false;
        }

        return view('public.book-show', compact('book', 'related', 'reviews', 'userReview', 'canReview', 'isBookAuthor'));
    }
}
