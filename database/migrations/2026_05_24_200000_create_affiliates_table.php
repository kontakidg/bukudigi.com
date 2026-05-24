<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');

            // Aplikasi & komitmen
            $table->string('promo_channels', 500)->nullable(); // contoh: "Instagram @xxx, TikTok @xxx, blog xxx.com"
            $table->text('motivation')->nullable();             // jawaban "kenapa mau jadi affiliate"
            $table->boolean('commitment_agreed')->default(false); // ceklis komitmen ToS
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();

            // Komisi & saldo
            $table->decimal('commission_rate', 5, 2)->default(10.00); // % komisi dari net amount
            $table->unsignedBigInteger('balance_available')->default(0);
            $table->unsignedBigInteger('balance_pending')->default(0);
            $table->unsignedBigInteger('total_earned')->default(0);

            // Stats cached
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_payout_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
