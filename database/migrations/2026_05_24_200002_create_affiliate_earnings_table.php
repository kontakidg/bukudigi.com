<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // komisi rupiah
            $table->decimal('rate_snapshot', 5, 2)->default(10.00); // simpan rate saat earning dibuat
            $table->enum('status', ['pending', 'available', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('available_at')->nullable(); // paid_at + cooling 7 hari
            $table->timestamp('paid_out_at')->nullable();
            $table->foreignId('payout_id')->nullable()->constrained('affiliate_payouts')->nullOnDelete();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_earnings');
    }
};
