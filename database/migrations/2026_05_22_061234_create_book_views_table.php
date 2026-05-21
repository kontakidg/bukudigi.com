<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['book_id', 'viewed_at']);
            $table->index('viewed_at');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('sales_count')->index();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
        Schema::dropIfExists('book_views');
    }
};
