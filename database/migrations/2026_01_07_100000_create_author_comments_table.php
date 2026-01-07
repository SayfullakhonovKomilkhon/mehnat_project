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
        Schema::create('author_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Author info
            $table->string('author_title')->nullable(); // e.g., "Huquqshunoslik fanlari doktori, professor"
            $table->string('organization')->nullable(); // e.g., "O'zbekiston Milliy Universiteti"
            
            // Comments in different languages
            $table->text('comment_uz');
            $table->text('comment_ru')->nullable();
            $table->text('comment_en')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            
            // Moderation
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // One author comment per article per user
            $table->unique(['article_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_comments');
    }
};

