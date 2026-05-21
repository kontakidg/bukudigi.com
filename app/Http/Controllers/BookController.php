<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

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

    public function show(Book $book)
    {
        abort_unless($book->status === 'active', 404);
        $book->load(['author', 'penName', 'category', 'tags']);

        $related = Book::active()
            ->where('id', '!=', $book->id)
            ->where('category_id', $book->category_id)
            ->with(['author', 'penName'])
            ->take(5)
            ->get();

        return view('public.book-show', compact('book', 'related'));
    }
}
