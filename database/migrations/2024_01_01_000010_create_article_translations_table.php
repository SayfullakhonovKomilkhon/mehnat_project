<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->char('locale', 2); // uz, ru, en
            $table->string('title', 500);
            $table->longText('content');
            $table->text('summary')->nullable();
            $table->jsonb('keywords')->default('[]');
            $table->timestamps();

            // Unique constraint: one translation per article per language
            $table->unique(['article_id', 'locale']);
            
            // Index for quick locale lookups
            $table->index('locale');
            $table->index(['locale', 'article_id']);
        });

        // Add full-text search index for PostgreSQL
        // This will be done in a separate migration after table creation
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_translations');
    }
};



