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
        // =============================================
        // 1. ADMIN Role - Full system access
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Администратор',
                'description' => 'Полный доступ ко всем функциям системы. Управление пользователями, контентом, модерация, статистика.',
                'permissions' => [
                    // User management
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.delete',
                    'users.change_role',
                    'users.change_status',
                    
                    // Content management - full CRUD
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
                    'articles.approve',
                    
                    // Comments moderation
                    'comments.view',
                    'comments.create',
                    'comments.update',
                    'comments.delete',
                    'comments.moderate',
                    'comments.approve',
                    
                    // Translations
                    'translations.view',
                    'translations.create',
                    'translations.update',
                    'translations.approve',
                    
                    // Analytics and logs
                    'analytics.view',
                    'logs.view',
                    
                    // System settings
                    'settings.view',
                    'settings.update',
                ],
            ]
        );

        // =============================================
        // 2. MUALLIF (Author) Role
        // - Write content for assigned articles/sections
        // - Create drafts
        // - Edit own content
        // - Submit for admin approval
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::MUALLIF],
            [
                'name' => 'Муаллиф (Автор)',
                'description' => 'Написание контента и комментариев для статей. Создание черновиков и отправка на модерацию.',
                'permissions' => [
                    // View content
                    'sections.view',
                    'chapters.view',
                    'articles.view',
                    
                    // Create and edit own content
                    'articles.create',
                    'articles.update_own',
                    'articles.submit_for_approval',
                    
                    // Comments - create author comments
                    'comments.view',
                    'comments.create',
                    'comments.update_own',
                    'comments.create_author_comment',
                    
                    // Drafts
                    'drafts.create',
                    'drafts.update_own',
                    'drafts.delete_own',
                ],
            ]
        );

        // =============================================
        // 3. TARJIMON (Translator) Role
        // - Translate Uzbek content to Russian and English
        // - Set translation status
        // - Submit for admin approval
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::TARJIMON],
            [
                'name' => 'Таржимон (Переводчик)',
                'description' => 'Перевод контента с узбекского на русский и английский языки.',
                'permissions' => [
                    // View content
                    'sections.view',
                    'chapters.view',
                    'articles.view',
                    
                    // Translations
                    'translations.view',
                    'translations.create',
                    'translations.update_own',
                    'translations.set_status',
                    'translations.submit_for_approval',
                    
                    // View comments for context
                    'comments.view',
                ],
            ]
        );

        // =============================================
        // 4. ISHCHI GURUH (Working Group) Role
        // - Enter technical data
        // - Create article/section structure
        // - Build categories/sections
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::ISHCHI_GURUH],
            [
                'name' => 'Ишчи гурух (Рабочая группа)',
                'description' => 'Создание структуры кодекса: разделы, главы, статьи. Ввод технических данных.',
                'permissions' => [
                    // Structure management - full CRUD
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
                    
                    // Categories
                    'categories.view',
                    'categories.create',
                    'categories.update',
                    'categories.delete',
                    
                    // Technical data
                    'technical_data.view',
                    'technical_data.create',
                    'technical_data.update',
                    
                    // Comments - view only
                    'comments.view',
                ],
            ]
        );

        // =============================================
        // 5. EKSPERT (Expert) Role
        // - Expert comments on articles
        // - Legal explanations
        // - Give recommendations
        // - Evaluate content
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::EKSPERT],
            [
                'name' => 'Эксперт',
                'description' => 'Экспертные комментарии к статьям, юридические разъяснения, рекомендации и оценка контента.',
                'permissions' => [
                    // View content
                    'sections.view',
                    'chapters.view',
                    'articles.view',
                    
                    // Expert comments
                    'comments.view',
                    'comments.create_expert_comment',
                    'comments.update_own',
                    
                    // Expert reviews
                    'expert_reviews.view',
                    'expert_reviews.create',
                    'expert_reviews.update_own',
                    
                    // Evaluations
                    'evaluations.view',
                    'evaluations.create',
                    'evaluations.update_own',
                    
                    // Recommendations
                    'recommendations.view',
                    'recommendations.create',
                    'recommendations.update_own',
                ],
            ]
        );

        // =============================================
        // 6. MODERATOR Role
        // - Content and comment moderation
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::MODERATOR],
            [
                'name' => 'Модератор',
                'description' => 'Модерация контента и комментариев, проверка переводов.',
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
                    
                    // Translations review
                    'translations.view',
                    'translations.approve',
                    
                    // Analytics (limited)
                    'analytics.view',
                ],
            ]
        );

        // =============================================
        // 7. USER Role - Basic permissions
        // =============================================
        Role::updateOrCreate(
            ['slug' => Role::USER],
            [
                'name' => 'Пользователь',
                'description' => 'Базовые права пользователя - просмотр контента и комментарии.',
                'permissions' => [
                    // View content
                    'sections.view',
                    'chapters.view',
                    'articles.view',
                    
                    // Comments - view and create own
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
                count($role->permissions ?? []),
            ])
        );
    }
}
