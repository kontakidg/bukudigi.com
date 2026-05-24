<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('ua_hash', 32)->nullable(); // md5 truncated of user-agent untuk dedup
            $table->string('referer', 500)->nullable();
            $table->timestamp('clicked_at')->useCurrent();

            $table->index(['affiliate_id', 'clicked_at']);
            $table->index(['affiliate_id', 'ip', 'clicked_at']); // utk dedup harian
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
    }
};
