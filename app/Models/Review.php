<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'book_id',
        'user_id',
        'order_id',
        'rating',
        'comment',
        'author_reply',
        'author_replied_at',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_hidden' => 'boolean',
            'author_replied_at' => 'datetime',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Recompute aggregate rating di book parent.
     * Dipanggil tiap review created/updated/deleted.
     */
    public function recomputeBookAggregate(): void
    {
        $book = $this->book;
        if (! $book) {
            return;
        }

        $visible = static::where('book_id', $book->id)->where('is_hidden', false);
        $count = $visible->count();
        $avg = $count > 0 ? round((clone $visible)->avg('rating'), 2) : 0;

        $book->update([
            'rating_avg' => $avg,
            'rating_count' => $count,
        ]);
    }

    protected static function booted(): void
    {
        static::saved(fn (Review $r) => $r->recomputeBookAggregate());
        static::deleted(fn (Review $r) => $r->recomputeBookAggregate());
    }
}
