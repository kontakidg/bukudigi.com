<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->foreignId('pen_name_id')
                ->nullable()
                ->after('author_id')
                ->constrained('pen_names')
                ->nullOnDelete();
        });

        // Backfill: untuk setiap book existing, set pen_name_id ke default
        // pen_name dari author-nya
        $books = DB::table('books')->select('id', 'author_id')->whereNull('pen_name_id')->get();

        foreach ($books as $book) {
            $defaultPen = DB::table('pen_names')
                ->where('author_id', $book->author_id)
                ->where('is_default', true)
                ->first();

            if ($defaultPen) {
                DB::table('books')->where('id', $book->id)->update([
                    'pen_name_id' => $defaultPen->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['pen_name_id']);
            $table->dropColumn('pen_name_id');
        });
    }
};
