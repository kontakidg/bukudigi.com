<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Book extends Model
{
    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'description',
        'table_of_contents',
        'cover_path',
        'cover_thumb_path',
        'cover_og_path',
        'pdf_master_path',
        'preview_pdf_path',
        'preview_image_paths',
        'page_count',
        'file_size_bytes',
        'price',
        'status',
        'ai_disclosure',
        'ai_platforms_used',
        'ai_moderation_result',
        'ai_score',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'sales_count',
        'total_revenue',
    ];

    protected function casts(): array
    {
        return [
            'preview_image_paths' => 'array',
            'ai_disclosure' => 'boolean',
            'ai_platforms_used' => 'array',
            'ai_moderation_result' => 'array',
            'ai_score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'price' => 'integer',
            'page_count' => 'integer',
            'file_size_bytes' => 'integer',
            'sales_count' => 'integer',
            'total_revenue' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Book $book) {
            if (empty($book->slug)) {
                $book->slug = Str::slug($book->title) . '-' . Str::random(6);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function formattedPrice(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
