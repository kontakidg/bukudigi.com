<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pen_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('author_id');
        });

        // Backfill: untuk setiap author yang sudah ada, buat 1 pen_name default
        // dari display_name + bio. Slug auto dari display_name.
        $authors = DB::table('authors')->select('id', 'display_name', 'bio')->get();

        foreach ($authors as $author) {
            $baseSlug = Str::slug($author->display_name) ?: 'penulis';
            $slug = $baseSlug;
            $i = 2;
            while (DB::table('pen_names')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            DB::table('pen_names')->insert([
                'author_id' => $author->id,
                'name' => $author->display_name,
                'slug' => $slug,
                'bio' => $author->bio,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pen_names');
    }
};
