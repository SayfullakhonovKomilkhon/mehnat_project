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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 255)->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('password');
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict');
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->char('preferred_locale', 2)->default('uz');
            
            // Two-Factor Authentication fields
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('role_id');
            $table->index('is_active');
            $table->index('email');
            $table->index('preferred_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};



