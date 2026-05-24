<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('discount_applied'); // Rupiah aktual yg dipotong
            $table->timestamp('used_at')->useCurrent();

            // 1 voucher 1 order — kalau order di-cancel/refund, masih track history
            $table->unique(['voucher_id', 'order_id']);
            $table->index(['user_id', 'voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};
