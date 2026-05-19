<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 32)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('commission');
            $table->unsignedBigInteger('author_earning');
            $table->unsignedBigInteger('gateway_fee')->default(0);
            $table->enum('status', [
                'pending',
                'paid',
                'watermarking',
                'ready',
                'refunded',
                'failed',
                'expired',
            ])->default('pending');
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('payment_method')->nullable();
            $table->json('midtrans_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('watermarked_pdf_path')->nullable();
            $table->timestamp('watermarked_at')->nullable();
            $table->timestamp('download_expires_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['author_id', 'paid_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
