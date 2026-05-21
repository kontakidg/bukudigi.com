<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        $defaults = [
            ['key' => 'hero_tagline_1', 'group' => 'homepage', 'value' => 'Ebook PDF dari Penulis Indonesia'],
            ['key' => 'hero_tagline_2', 'group' => 'homepage', 'value' => 'Bayar QRIS, Baca Sekarang'],
            ['key' => 'hero_subtagline', 'group' => 'homepage', 'value' => 'Marketplace ebook untuk komunitas pembaca & penulis lokal. Watermark personal di setiap halaman, dukungan langsung ke penulis.'],
            ['key' => 'site_tagline_meta', 'group' => 'seo', 'value' => 'Marketplace ebook PDF dari penulis Indonesia. Bayar QRIS, instan download, watermark personal.'],
        ];
        foreach ($defaults as $row) {
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
