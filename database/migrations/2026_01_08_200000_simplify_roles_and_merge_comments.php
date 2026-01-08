<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 1. Remove all roles except admin and user
     * 2. Merge author_comments and expertises into article_comments
     */
    public function up(): void
    {
        // Step 1: Create new unified article_comments table
        Schema::create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            
            // Comment content in multiple languages
            $table->text('comment_uz')->nullable();
            $table->text('comment_ru')->nullable();
            $table->text('comment_en')->nullable();
            
            // Expert sections (optional)
            $table->text('legal_references')->nullable(); // JSON: international and national laws
            $table->text('court_practice')->nullable();
            $table->text('recommendations')->nullable(); // Examples (МИСОЛЛАР)
            
            // Meta
            $table->string('author_name')->nullable();
            $table->string('author_title')->nullable();
            $table->string('organization')->nullable();
            
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('approved');
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // One comment per article
            $table->unique('article_id');
        });

        // Step 2: Migrate data from author_comments
        $authorComments = DB::table('author_comments')
            ->where('status', 'approved')
            ->get();
            
        foreach ($authorComments as $ac) {
            DB::table('article_comments')->updateOrInsert(
                ['article_id' => $ac->article_id],
                [
                    'comment_uz' => $ac->comment_uz,
                    'comment_ru' => $ac->comment_ru,
                    'comment_en' => $ac->comment_en,
                    'author_title' => $ac->author_title,
                    'organization' => $ac->organization,
                    'status' => 'approved',
                    'created_at' => $ac->created_at,
                    'updated_at' => now(),
                ]
            );
        }

        // Step 3: Merge expertises data into article_comments
        $expertises = DB::table('expertises')
            ->where('status', 'approved')
            ->get();
            
        foreach ($expertises as $exp) {
            $existing = DB::table('article_comments')
                ->where('article_id', $exp->article_id)
                ->first();
                
            if ($existing) {
                // Update existing record with expertise data
                DB::table('article_comments')
                    ->where('article_id', $exp->article_id)
                    ->update([
                        'legal_references' => $exp->legal_references,
                        'court_practice' => $exp->court_practice,
                        'recommendations' => $exp->recommendations,
                        'updated_at' => now(),
                    ]);
            } else {
                // Create new record from expertise
                DB::table('article_comments')->insert([
                    'article_id' => $exp->article_id,
                    'comment_uz' => $exp->expert_comment,
                    'legal_references' => $exp->legal_references,
                    'court_practice' => $exp->court_practice,
                    'recommendations' => $exp->recommendations,
                    'status' => 'approved',
                    'created_at' => $exp->created_at,
                    'updated_at' => now(),
                ]);
            }
        }

        // Step 4: Get admin role ID
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $userRole = DB::table('roles')->where('slug', 'user')->first();
        
        if ($adminRole && $userRole) {
            // Update all non-admin users to user role
            DB::table('users')
                ->where('role_id', '!=', $adminRole->id)
                ->update(['role_id' => $userRole->id]);
        }

        // Step 5: Remove old roles (keep admin and user only)
        DB::table('roles')
            ->whereNotIn('slug', ['admin', 'user'])
            ->delete();

        // Step 6: Drop old tables
        Schema::dropIfExists('author_comments');
        Schema::dropIfExists('expertises');
        Schema::dropIfExists('muallif_assignments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate author_comments table
        Schema::create('author_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('author_title')->nullable();
            $table->string('organization')->nullable();
            $table->text('comment_uz')->nullable();
            $table->text('comment_ru')->nullable();
            $table->text('comment_en')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Recreate expertises table
        Schema::create('expertises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('expert_comment')->nullable();
            $table->json('legal_references')->nullable();
            $table->text('court_practice')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Drop new table
        Schema::dropIfExists('article_comments');
    }
};

