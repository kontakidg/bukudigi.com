<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // gross_amount existing = harga buku (before discount).
            // voucher_discount = potongan dari voucher.
            // net_amount = gross - discount = yang dibayar customer ke Midtrans.
            $table->foreignId('voucher_id')->nullable()->after('book_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('voucher_discount')->default(0)->after('gross_amount');
            $table->unsignedInteger('net_amount')->default(0)->after('voucher_discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['voucher_id']);
            $table->dropColumn(['voucher_id', 'voucher_discount', 'net_amount']);
        });
    }
};
