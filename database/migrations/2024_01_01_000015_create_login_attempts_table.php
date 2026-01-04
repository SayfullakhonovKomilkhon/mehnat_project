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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // IPv6 support
            $table->string('email', 255)->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at');

            // Indexes for brute-force detection queries
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
            $table->index('attempted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};



