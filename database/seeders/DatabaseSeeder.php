<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            SampleDataSeeder::class, // Sample data for sections, chapters, articles
            LaborCodeSeeder::class, // Labor Code structure (sections, chapters, articles)
            LaborCodeContentSeeder::class, // Full content with comments (ШАРҲ)
            AuthorExpertSeeder::class, // Author comments and expert conclusions
        ]);
    }
}



