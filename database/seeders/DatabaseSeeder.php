<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Simplified: Only admin and user roles, unified article comments
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,           // Only admin and user roles
            AdminUserSeeder::class,       // Admin user
            LaborCodeSeeder::class,       // Labor Code structure (sections, chapters, articles)
            LaborCodeContentSeeder::class, // Full content
        ]);
    }
}
