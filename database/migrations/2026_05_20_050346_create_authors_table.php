<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('nik', 32)->nullable();
            $table->string('npwp', 32)->nullable();
            $table->string('bank_name', 64)->nullable();
            $table->string('bank_account', 64)->nullable();
            $table->string('bank_holder')->nullable();
            $table->string('ktp_image_path')->nullable();
            $table->string('selfie_image_path')->nullable();
            $table->enum('status', ['pending', 'verified', 'suspended'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('balance_available')->default(0);
            $table->unsignedBigInteger('balance_pending')->default(0);
            $table->unsignedBigInteger('total_earned')->default(0);
            $table->timestamp('last_payout_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
