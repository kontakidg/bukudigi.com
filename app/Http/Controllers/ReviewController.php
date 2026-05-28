<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Submit / update review (pembeli only).
     */
    public function store(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        // Cek user sudah beli buku ini (order paid/watermarking/ready)
        $order = Order::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->whereIn('status', ['paid', 'watermarking', 'ready'])
            ->latest()
            ->first();

        if (! $order) {
            return back()->withErrors(['review' => 'Kamu harus beli buku ini dulu sebelum kasih review.']);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.required' => 'Pilih rating bintang dulu.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
        ]);

        Review::updateOrCreate(
            ['book_id' => $book->id, 'user_id' => $user->id],
            [
                'order_id' => $order->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'is_hidden' => false,
            ]
        );

        return back()->with('status', 'Makasih! Review kamu sudah tampil.')
            ->withFragment('reviews');
    }

    /**
     * Hapus review sendiri.
     */
    public function destroy(Request $request, Review $review): RedirectResponse
    {
        abort_unless($review->user_id === $request->user()->id, 403);

        $review->delete();

        return back()->with('status', 'Review kamu sudah dihapus.')
            ->withFragment('reviews');
    }

    /**
     * Author reply ke review (author buku tersebut only).
     */
    public function reply(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();
        $review->load('book.author');

        // Pastikan user adalah author buku ini
        abort_unless(
            $user->author && $review->book->author_id === $user->author->id,
            403,
            'Hanya penulis buku ini yang boleh membalas.'
        );

        $data = $request->validate([
            'author_reply' => ['required', 'string', 'max:1000'],
        ], [
            'author_reply.required' => 'Tulis balasan dulu.',
        ]);

        $review->update([
            'author_reply' => $data['author_reply'],
            'author_replied_at' => now(),
        ]);

        return back()->with('status', 'Balasan kamu sudah tampil.');
    }
}
