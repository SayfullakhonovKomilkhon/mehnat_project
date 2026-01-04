<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Section $section;
    protected Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'permissions' => ['*'],
        ]);

        $userRole = Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => ['comments.create'],
        ]);

        // Create users
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'is_active' => true,
        ]);

        // Create section and chapter
        $this->section = Section::create([
            'order_number' => 1,
            'is_active' => true,
        ]);

        $this->section->translations()->create([
            'locale' => 'ru',
            'title' => 'Тестовый раздел',
        ]);

        $this->chapter = Chapter::create([
            'section_id' => $this->section->id,
            'order_number' => 1,
            'is_active' => true,
        ]);

        $this->chapter->translations()->create([
            'locale' => 'ru',
            'title' => 'Тестовая глава',
        ]);
    }

    public function test_can_get_articles_list(): void
    {
        $article = Article::create([
            'chapter_id' => $this->chapter->id,
            'article_number' => '1',
            'order_number' => 1,
            'is_active' => true,
        ]);

        $article->translations()->create([
            'locale' => 'ru',
            'title' => 'Статья 1',
            'content' => 'Содержимое статьи',
        ]);

        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'pagination',
                ],
            ]);
    }

    public function test_can_get_single_article(): void
    {
        $article = Article::create([
            'chapter_id' => $this->chapter->id,
            'article_number' => '1',
            'order_number' => 1,
            'is_active' => true,
        ]);

        $article->translations()->create([
            'locale' => 'ru',
            'title' => 'Статья 1',
            'content' => 'Содержимое статьи',
        ]);

        $response = $this->getJson("/api/v1/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.article_number', '1');
    }

    public function test_viewing_article_increments_views(): void
    {
        $article = Article::create([
            'chapter_id' => $this->chapter->id,
            'article_number' => '1',
            'order_number' => 1,
            'is_active' => true,
            'views_count' => 0,
        ]);

        $article->translations()->create([
            'locale' => 'ru',
            'title' => 'Статья 1',
            'content' => 'Содержимое статьи',
        ]);

        $this->getJson("/api/v1/articles/{$article->id}");

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'views_count' => 1,
        ]);
    }

    public function test_admin_can_create_article(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/articles', [
                'chapter_id' => $this->chapter->id,
                'article_number' => '77',
                'order_number' => 1,
                'translations' => [
                    'uz' => [
                        'title' => 'Modda 77',
                        'content' => 'Modda matni',
                    ],
                    'ru' => [
                        'title' => 'Статья 77',
                        'content' => 'Текст статьи',
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('articles', [
            'article_number' => '77',
        ]);
    }

    public function test_regular_user_cannot_create_article(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/articles', [
                'chapter_id' => $this->chapter->id,
                'article_number' => '77',
                'order_number' => 1,
                'translations' => [
                    'uz' => [
                        'title' => 'Modda 77',
                        'content' => 'Modda matni',
                    ],
                ],
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_article(): void
    {
        $article = Article::create([
            'chapter_id' => $this->chapter->id,
            'article_number' => '1',
            'order_number' => 1,
            'is_active' => true,
        ]);

        $article->translations()->create([
            'locale' => 'ru',
            'title' => 'Старое название',
            'content' => 'Старое содержимое',
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/admin/articles/{$article->id}", [
                'translations' => [
                    'ru' => [
                        'title' => 'Новое название',
                        'content' => 'Новое содержимое',
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('article_translations', [
            'article_id' => $article->id,
            'locale' => 'ru',
            'title' => 'Новое название',
        ]);
    }

    public function test_inactive_article_not_visible_to_public(): void
    {
        $article = Article::create([
            'chapter_id' => $this->chapter->id,
            'article_number' => '1',
            'order_number' => 1,
            'is_active' => false,
        ]);

        $article->translations()->create([
            'locale' => 'ru',
            'title' => 'Скрытая статья',
            'content' => 'Содержимое',
        ]);

        $response = $this->getJson("/api/v1/articles/{$article->id}");

        $response->assertStatus(404);
    }

    public function test_search_returns_results(): void
    {
        $article = Article::create([
            'chapter_id' => $this->chapter->id,
            'article_number' => '77',
            'order_number' => 1,
            'is_active' => true,
        ]);

        $article->translations()->create([
            'locale' => 'ru',
            'title' => 'Трудовой договор',
            'content' => 'Статья о трудовом договоре',
        ]);

        // Note: Full-text search requires PostgreSQL, this is a basic test
        $response = $this->getJson('/api/v1/search?q=трудовой');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'query',
                    'items',
                    'pagination',
                ],
            ]);
    }
}



