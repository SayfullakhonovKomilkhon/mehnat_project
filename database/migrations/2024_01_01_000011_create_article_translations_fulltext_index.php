<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates full-text search index on article_translations for PostgreSQL
     */
    public function up(): void
    {
        // Only run for PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }
        
        // Add tsvector column for full-text search
        DB::statement('ALTER TABLE article_translations ADD COLUMN IF NOT EXISTS search_vector tsvector');
        
        // Create GIN index on search_vector
        DB::statement('CREATE INDEX IF NOT EXISTS article_translations_search_idx ON article_translations USING GIN(search_vector)');
        
        // Create function to update search vector
        DB::statement("
            CREATE OR REPLACE FUNCTION update_article_search_vector()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.search_vector := 
                    setweight(to_tsvector('simple', COALESCE(NEW.title, '')), 'A') ||
                    setweight(to_tsvector('simple', COALESCE(NEW.summary, '')), 'B') ||
                    setweight(to_tsvector('simple', COALESCE(NEW.content, '')), 'C');
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
        
        // Create trigger to auto-update search vector
        DB::statement('
            DROP TRIGGER IF EXISTS article_translations_search_update ON article_translations;
            CREATE TRIGGER article_translations_search_update
            BEFORE INSERT OR UPDATE ON article_translations
            FOR EACH ROW EXECUTE FUNCTION update_article_search_vector();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run for PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }
        
        DB::statement('DROP TRIGGER IF EXISTS article_translations_search_update ON article_translations');
        DB::statement('DROP FUNCTION IF EXISTS update_article_search_vector()');
        DB::statement('DROP INDEX IF EXISTS article_translations_search_idx');
        DB::statement('ALTER TABLE article_translations DROP COLUMN IF EXISTS search_vector');
    }
};



