<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', Role::ADMIN)->first();

        if (!$adminRole) {
            $this->command->error('Admin role not found. Run RoleSeeder first.');
            return;
        }

        // Check if admin already exists
        $existingAdmin = User::where('email', 'admin@mehnat-kodeksi.uz')->first();

        if ($existingAdmin) {
            $this->command->warn('Admin user already exists.');
            return;
        }

        // Generate secure random password
        $password = Str::random(16);

        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@mehnat-kodeksi.uz',
            'password' => Hash::make($password),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
            'is_active' => true,
            'preferred_locale' => 'ru',
        ]);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('   ADMIN USER CREATED SUCCESSFULLY');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->line('   Email:    admin@mehnat-kodeksi.uz');
        $this->command->line("   Password: {$password}");
        $this->command->info('');
        $this->command->warn('   Please change this password after first login!');
        $this->command->info('========================================');
        $this->command->info('');
    }
}



