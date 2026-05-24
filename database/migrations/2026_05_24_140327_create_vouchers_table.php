<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();

            // Discount config
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->unsignedInteger('discount_value');           // % (1-100) atau Rupiah
            $table->unsignedInteger('max_discount_amount')->nullable(); // Cap untuk percentage
            $table->unsignedInteger('min_purchase_amount')->nullable(); // Minimum total order

            // Usage limits
            $table->unsignedInteger('max_uses')->nullable();     // Total max, null = unlimited
            $table->unsignedInteger('max_uses_per_user')->default(1);
            $table->unsignedInteger('used_count')->default(0);

            // Validity
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            // Scope (null = berlaku untuk semua)
            $table->json('applicable_book_ids')->nullable();
            $table->json('applicable_category_ids')->nullable();

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
