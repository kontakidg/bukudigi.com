<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('table_of_contents')->nullable();
            $table->string('cover_path');
            $table->string('cover_thumb_path')->nullable();
            $table->string('cover_og_path')->nullable();
            $table->string('pdf_master_path');
            $table->string('preview_pdf_path')->nullable();
            $table->json('preview_image_paths')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->unsignedBigInteger('price');
            $table->enum('status', ['draft', 'pending_review', 'active', 'rejected', 'archived'])->default('draft');
            $table->boolean('ai_disclosure')->default(false);
            $table->json('ai_platforms_used')->nullable();
            $table->json('ai_moderation_result')->nullable();
            $table->decimal('ai_score', 4, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('sales_count')->default(0);
            $table->unsignedBigInteger('total_revenue')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('category_id');
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
