<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Cache aggregate rating (di-recompute tiap review berubah) — hemat query di listing
            $table->decimal('rating_avg', 3, 2)->default(0)->after('sales_count');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'rating_count']);
        });
    }
};
