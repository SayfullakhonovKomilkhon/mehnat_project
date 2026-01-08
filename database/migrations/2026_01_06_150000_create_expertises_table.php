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
        Schema::create('expertises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('expert_comment');
            $table->json('legal_references')->nullable();
            $table->text('court_practice')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one expertise per article per user
            $table->unique(['article_id', 'user_id']);
            
            // Indexes for faster queries
            $table->index('status');
            $table->index(['article_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expertises');
    }
};


