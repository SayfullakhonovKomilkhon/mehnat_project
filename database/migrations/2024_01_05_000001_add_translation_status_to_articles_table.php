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
        Schema::table('articles', function (Blueprint $table) {
            $table->enum('translation_status', ['draft', 'pending', 'approved'])
                  ->default('draft')
                  ->after('is_active')
                  ->comment('Translation workflow status: draft (not submitted), pending (awaiting admin approval), approved (translation complete)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('translation_status');
        });
    }
};

