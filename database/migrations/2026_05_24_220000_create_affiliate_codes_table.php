<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pisahkan code dari affiliates jadi tabel sendiri (1 affiliate bisa punya max 10 code).
 * Migrate existing affiliates.code → 1 affiliate_codes record (is_default=true).
 * Tambah affiliate_code_id (nullable) ke clicks, earnings, orders untuk attribution per-code.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('label', 60)->nullable(); // cth: "Instagram bio", "TikTok video viral"
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->unsignedBigInteger('total_earned')->default(0);
            $table->timestamps();

            $table->index('affiliate_id');
        });

        // Migrate existing affiliates.code → 1 row per affiliate (default)
        $rows = DB::table('affiliates')->select('id', 'code', 'clicks_count', 'conversions_count', 'total_earned', 'created_at')->get();
        foreach ($rows as $r) {
            DB::table('affiliate_codes')->insert([
                'affiliate_id' => $r->id,
                'code' => $r->code,
                'label' => 'Default',
                'is_default' => true,
                'clicks_count' => $r->clicks_count,
                'conversions_count' => $r->conversions_count,
                'total_earned' => $r->total_earned,
                'created_at' => $r->created_at,
                'updated_at' => now(),
            ]);
        }

        // Drop kolom code dari affiliates (tidak lagi authoritative)
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        // Tambah affiliate_code_id ke clicks
        Schema::table('affiliate_clicks', function (Blueprint $table) {
            $table->foreignId('affiliate_code_id')->nullable()->after('affiliate_id')
                ->constrained('affiliate_codes')->nullOnDelete();
        });

        // Tambah affiliate_code_id ke earnings
        Schema::table('affiliate_earnings', function (Blueprint $table) {
            $table->foreignId('affiliate_code_id')->nullable()->after('affiliate_id')
                ->constrained('affiliate_codes')->nullOnDelete();
        });

        // Tambah affiliate_code_id ke orders + populate dari affiliate_code string (lookup default)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('affiliate_code_id')->nullable()->after('affiliate_code')
                ->constrained('affiliate_codes')->nullOnDelete();
        });

        // Backfill orders.affiliate_code_id dari affiliate_code string
        if (DB::table('orders')->whereNotNull('affiliate_code')->exists()) {
            $orders = DB::table('orders')->whereNotNull('affiliate_code')->select('id', 'affiliate_code')->get();
            foreach ($orders as $o) {
                $codeId = DB::table('affiliate_codes')->where('code', $o->affiliate_code)->value('id');
                if ($codeId) {
                    DB::table('orders')->where('id', $o->id)->update(['affiliate_code_id' => $codeId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliate_code_id');
        });
        Schema::table('affiliate_earnings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliate_code_id');
        });
        Schema::table('affiliate_clicks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliate_code_id');
        });
        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->unique()->after('user_id');
        });

        // Restore default code per affiliate
        $defaults = DB::table('affiliate_codes')->where('is_default', true)->get();
        foreach ($defaults as $d) {
            DB::table('affiliates')->where('id', $d->affiliate_id)->update(['code' => $d->code]);
        }

        Schema::dropIfExists('affiliate_codes');
    }
};
