<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->enum('role', ['user', 'author', 'admin'])->default('user')->after('phone');
            $table->string('google_id')->nullable()->unique()->after('role');
            $table->timestamp('wa_verified_at')->nullable()->after('email_verified_at');
            $table->string('avatar_path')->nullable()->after('wa_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'google_id', 'wa_verified_at', 'avatar_path']);
        });
    }
};
