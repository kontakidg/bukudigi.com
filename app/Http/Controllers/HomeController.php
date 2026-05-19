<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('public.home', [
            'stats' => [
                'total_books' => Book::active()->count(),
                'total_authors' => Author::where('status', 'verified')->count(),
                'total_orders' => Order::where('status', 'ready')->count(),
            ],
            'categories' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->take(9)
                ->get(),
            'newestBooks' => Book::active()
                ->with(['author', 'category'])
                ->latest('approved_at')
                ->take(10)
                ->get(),
            'popularBooks' => Book::active()
                ->with(['author', 'category'])
                ->orderByDesc('sales_count')
                ->take(10)
                ->get(),
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $books = Book::active()
            ->with(['author', 'category'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'LIKE', "%{$q}%")
                      ->orWhere('description', 'LIKE', "%{$q}%");
                });
            })
            ->paginate(20)
            ->withQueryString();

        return view('public.search', compact('books', 'q'));
    }

    public function placeholder(string $slug)
    {
        return view('public.placeholder', ['slug' => $slug]);
    }
}
