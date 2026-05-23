<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Master EPUB (optional) yang di-upload author
        Schema::table('books', function (Blueprint $table) {
            $table->string('epub_master_path', 500)->nullable()->after('pdf_master_path');
        });

        // Per-buyer EPUB hasil watermark metadata
        Schema::table('orders', function (Blueprint $table) {
            $table->string('watermarked_epub_path', 500)->nullable()->after('watermarked_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('watermarked_epub_path');
        });
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('epub_master_path');
        });
    }
};
