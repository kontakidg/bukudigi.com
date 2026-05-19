<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->withCount(['books' => fn ($q) => $q->where('status', 'active')])
            ->get();

        return view('public.kategori-index', compact('categories'));
    }

    public function show(Category $category)
    {
        $books = Book::active()
            ->where('category_id', $category->id)
            ->with(['author'])
            ->latest('approved_at')
            ->paginate(24);

        return view('public.kategori-show', compact('category', 'books'));
    }
}
