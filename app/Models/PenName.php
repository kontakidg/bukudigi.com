<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PenName extends Model
{
    protected $fillable = [
        'author_id',
        'name',
        'slug',
        'bio',
        'avatar_path',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PenName $pn) {
            if (empty($pn->slug)) {
                $base = Str::slug($pn->name) ?: 'penulis';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $pn->id)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $pn->slug = $slug;
            }
        });

        // Pastikan hanya 1 default per author
        static::saving(function (PenName $pn) {
            if ($pn->is_default) {
                static::where('author_id', $pn->author_id)
                    ->where('id', '!=', $pn->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
