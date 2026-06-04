<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('author_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();

            // Periode & nominal
            $table->string('period', 7);                        // YYYY-MM
            $table->unsignedBigInteger('gross_earning');        // total royalti sebelum potongan
            $table->unsignedTinyInteger('pph23_rate')->default(2); // 2% atau 4%
            $table->unsignedBigInteger('pph23_amount');         // nominal PPh23
            $table->unsignedBigInteger('transfer_fee')->default(3250); // biaya transfer (50:50)
            $table->unsignedBigInteger('net_transfer');         // yang benar-benar ditransfer

            // Snapshot rekening saat payout (bisa berubah nanti)
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_holder')->nullable();
            $table->string('npwp')->nullable();

            // Status & tracking
            $table->enum('status', ['requested', 'processing', 'processed', 'failed'])
                  ->default('requested');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            // Admin
            $table->string('bank_ref')->nullable();             // nomor ref transfer bank
            $table->text('admin_note')->nullable();
            $table->string('bank_proof_path')->nullable();      // bukti transfer (upload)

            $table->timestamps();

            $table->index(['author_id', 'period']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_payouts');
    }
};
