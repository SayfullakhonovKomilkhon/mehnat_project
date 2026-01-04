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
        Schema::create('chapter_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
            $table->char('locale', 2); // uz, ru, en
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->timestamps();

            // Unique constraint: one translation per chapter per language
            $table->unique(['chapter_id', 'locale']);
            
            // Index for quick locale lookups
            $table->index('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapter_translations');
    }
};



