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
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->uuid('session_id');
            $table->text('user_message');
            $table->text('bot_response');
            $table->char('locale', 2)->default('uz');
            $table->jsonb('related_article_ids')->default('[]');
            $table->decimal('confidence_score', 3, 2)->nullable(); // 0.00 to 1.00
            $table->boolean('was_helpful')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('session_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index('locale');
            $table->index('was_helpful');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};



