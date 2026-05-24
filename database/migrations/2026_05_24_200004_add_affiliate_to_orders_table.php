<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('affiliate_id')->nullable()->after('voucher_id')
                ->constrained('affiliates')->nullOnDelete();
            $table->string('affiliate_code', 32)->nullable()->after('affiliate_id');
            $table->unsignedBigInteger('affiliate_commission')->default(0)->after('commission');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliate_id');
            $table->dropColumn(['affiliate_code', 'affiliate_commission']);
        });
    }
};
