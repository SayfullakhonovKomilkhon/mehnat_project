<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Simplified roles - only admin and user
     */
    public function run(): void
    {
        // =============================================
        // 1. ADMIN Role - Full system access
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Администратор',
                'description' => 'Полный доступ ко всем функциям системы.',
                'permissions' => [
                    // Full access
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.delete',
                    'sections.view',
                    'sections.create',
                    'sections.update',
                    'sections.delete',
                    'chapters.view',
                    'chapters.create',
                    'chapters.update',
                    'chapters.delete',
                    'articles.view',
                    'articles.create',
                    'articles.update',
                    'articles.delete',
                    'comments.view',
                    'comments.create',
                    'comments.update',
                    'comments.delete',
                    'analytics.view',
                    'logs.view',
                    'settings.view',
                    'settings.update',
                ],
            ]
        );

        // =============================================
        // 2. USER Role - Basic permissions
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::USER],
            [
                'name' => 'Пользователь',
                'description' => 'Базовые права пользователя - просмотр контента.',
                'permissions' => [
                    'sections.view',
                    'chapters.view',
                    'articles.view',
                    'comments.view',
                ],
            ]
        );

        $this->command->info('Roles seeded successfully!');
        $this->command->table(
            ['Slug', 'Name', 'Permissions Count'],
            Role::all()->map(fn ($role) => [
                $role->slug,
                $role->name,
                count($role->permissions ?? []),
            ])
        );
    }
}
