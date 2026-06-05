<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_path',
        'category',
        'author_name',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'views_count',
        'fb_posted_at',
        'fb_post_id',
        'created_by',
        'ai_generated',
        'ai_status',
        'ai_error',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'fb_posted_at' => 'datetime',
            'views_count'  => 'integer',
            'ai_generated' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title) . '-' . Str::lower(Str::random(6));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Artikel yang sudah live: published & published_at sudah lewat. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /** URL cover (handle http/https atau public disk), fallback OG default. */
    public function coverUrl(): string
    {
        if ($this->cover_path) {
            return Str::startsWith($this->cover_path, ['http://', 'https://'])
                ? $this->cover_path
                : asset('storage/' . $this->cover_path);
        }
        return asset('og-default.png');
    }

    /** Siap dipost ke FB: published & belum pernah dipost. */
    public function isReadyToPost(): bool
    {
        return $this->status === 'published' && $this->fb_posted_at === null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'scheduled' => 'Terjadwal',
            'published' => 'Terbit',
            'archived'  => 'Arsip',
            default     => ucfirst($this->status),
        };
    }

    public function aiStatusLabel(): ?string
    {
        return match ($this->ai_status) {
            'pending'    => 'Antri',
            'generating' => 'Proses',
            'done'       => 'Selesai',
            'failed'     => 'Gagal',
            default      => null,
        };
    }
}
