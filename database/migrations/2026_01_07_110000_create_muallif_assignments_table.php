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
        Schema::create('muallif_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Muallif user
            
            // Assignment can be for a specific article, chapter, or section
            $table->foreignId('article_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Who assigned this
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            
            // Assignment type: 'article', 'chapter', 'section'
            $table->enum('assignment_type', ['article', 'chapter', 'section']);
            
            // Notes from admin
            $table->text('notes')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Prevent duplicate assignments
            $table->unique(['user_id', 'article_id'], 'unique_article_assignment');
            $table->unique(['user_id', 'chapter_id'], 'unique_chapter_assignment');
            $table->unique(['user_id', 'section_id'], 'unique_section_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('muallif_assignments');
    }
};


