<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Payment gateway tracking — stub | paypal | midtrans
            $table->string('payment_gateway', 20)->nullable()->after('payment_method');
            // PayPal-specific identifiers
            $table->string('paypal_order_id', 64)->nullable()->after('payment_gateway')->index();
            $table->string('paypal_capture_id', 64)->nullable()->after('paypal_order_id');
            // USD amount yang ditagihkan via PayPal (IDR utama tetap di gross/net_amount)
            $table->decimal('paypal_amount_usd', 10, 2)->nullable()->after('paypal_capture_id');
            $table->decimal('paypal_usd_rate', 12, 4)->nullable()->after('paypal_amount_usd');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'paypal_order_id',
                'paypal_capture_id',
                'paypal_amount_usd',
                'paypal_usd_rate',
            ]);
        });
    }
};
