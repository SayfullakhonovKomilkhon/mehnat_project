<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin Role - All permissions
        Role::updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Администратор',
                'description' => 'Полный доступ ко всем функциям системы',
                'permissions' => [
                    // User management
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.delete',
                    'users.change_role',
                    'users.change_status',
                    
                    // Content management
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
                    
                    // Comments moderation
                    'comments.view',
                    'comments.create',
                    'comments.update',
                    'comments.delete',
                    'comments.moderate',
                    
                    // Analytics and logs
                    'analytics.view',
                    'logs.view',
                    
                    // System settings
                    'settings.view',
                    'settings.update',
                ],
            ]
        );

        // Moderator Role - Content and comment moderation
        Role::updateOrCreate(
            ['slug' => Role::MODERATOR],
            [
                'name' => 'Модератор',
                'description' => 'Модерация контента и комментариев',
                'permissions' => [
                    // Content management
                    'sections.view',
                    'sections.update',
                    'chapters.view',
                    'chapters.update',
                    'articles.view',
                    'articles.create',
                    'articles.update',
                    
                    // Comments moderation
                    'comments.view',
                    'comments.create',
                    'comments.update',
                    'comments.delete',
                    'comments.moderate',
                    
                    // Analytics (limited)
                    'analytics.view',
                ],
            ]
        );

        // User Role - Basic permissions
        Role::updateOrCreate(
            ['slug' => Role::USER],
            [
                'name' => 'Пользователь',
                'description' => 'Базовые права пользователя',
                'permissions' => [
                    // View content
                    'sections.view',
                    'chapters.view',
                    'articles.view',
                    
                    // Comments
                    'comments.view',
                    'comments.create',
                ],
            ]
        );

        $this->command->info('Roles seeded successfully!');
        $this->command->table(
            ['Slug', 'Name', 'Permissions Count'],
            Role::all()->map(fn ($role) => [
                $role->slug,
                $role->name,
                count($role->permissions),
            ])
        );
    }
}



