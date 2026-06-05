<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('ai_generated')->default(false)->after('created_by');
            $table->string('ai_status')->nullable()->after('ai_generated'); // pending|generating|done|failed
            $table->text('ai_error')->nullable()->after('ai_status');

            $table->index('ai_status');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['ai_status']);
            $table->dropColumn(['ai_generated', 'ai_status', 'ai_error']);
        });
    }
};
