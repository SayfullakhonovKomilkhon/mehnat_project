<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $moderator;
    protected User $user;
    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'permissions' => ['*'],
        ]);

        $moderatorRole = Role::create([
            'name' => 'Moderator',
            'slug' => Role::MODERATOR,
            'permissions' => ['comments.moderate'],
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

        $this->moderator = User::create([
            'name' => 'Moderator',
            'email' => 'moderator@example.com',
            'password' => Hash::make('password'),
            'role_id' => $moderatorRole->id,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'is_active' => true,
        ]);

        // Create article
        $section = Section::create(['order_number' => 1, 'is_active' => true]);
        $section->translations()->create(['locale' => 'ru', 'title' => 'Раздел']);

        $chapter = Chapter::create([
            'section_id' => $section->id,
            'order_number' => 1,
            'is_active' => true,
        ]);
        $chapter->translations()->create(['locale' => 'ru', 'title' => 'Глава']);

        $this->article = Article::create([
            'chapter_id' => $chapter->id,
            'article_number' => '1',
            'order_number' => 1,
            'is_active' => true,
        ]);
        $this->article->translations()->create([
            'locale' => 'ru',
            'title' => 'Статья 1',
            'content' => 'Содержимое',
        ]);
    }

    public function test_authenticated_user_can_create_comment(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/articles/{$this->article->id}/comments", [
                'content' => 'Это тестовый комментарий с достаточным текстом.',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_comment(): void
    {
        $response = $this->postJson("/api/v1/articles/{$this->article->id}/comments", [
            'content' => 'Это тестовый комментарий с достаточным текстом.',
        ]);

        $response->assertStatus(401);
    }

    public function test_comment_validation(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/articles/{$this->article->id}/comments", [
                'content' => 'Short', // Too short
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_update_own_comment(): void
    {
        $comment = Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'content' => 'Исходный комментарий для теста',
            'status' => Comment::STATUS_APPROVED,
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Обновленный комментарий для теста',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Обновленный комментарий для теста',
        ]);
    }

    public function test_user_cannot_update_others_comment(): void
    {
        $comment = Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->admin->id,
            'content' => 'Комментарий админа для теста',
            'status' => Comment::STATUS_APPROVED,
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Попытка изменить чужой комментарий',
            ]);

        $response->assertStatus(403);
    }

    public function test_moderator_can_approve_comment(): void
    {
        $comment = Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'content' => 'Комментарий на проверке для теста',
            'status' => Comment::STATUS_PENDING,
        ]);

        $token = $this->moderator->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/comments/{$comment->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => Comment::STATUS_APPROVED,
            'moderated_by' => $this->moderator->id,
        ]);
    }

    public function test_moderator_can_reject_comment(): void
    {
        $comment = Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'content' => 'Комментарий на проверке для теста',
            'status' => Comment::STATUS_PENDING,
        ]);

        $token = $this->moderator->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/comments/{$comment->id}/reject");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => Comment::STATUS_REJECTED,
        ]);
    }

    public function test_user_can_like_comment(): void
    {
        $comment = Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->admin->id,
            'content' => 'Комментарий для лайка тестирования',
            'status' => Comment::STATUS_APPROVED,
            'likes_count' => 0,
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/comments/{$comment->id}/like");

        $response->assertStatus(200)
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);
    }

    public function test_user_can_unlike_comment(): void
    {
        $comment = Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->admin->id,
            'content' => 'Комментарий для анлайка тестирования',
            'status' => Comment::STATUS_APPROVED,
            'likes_count' => 1,
        ]);

        $comment->likes()->create(['user_id' => $this->user->id]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/comments/{$comment->id}/like");

        $response->assertStatus(200)
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);
    }

    public function test_only_approved_comments_visible_to_public(): void
    {
        // Create approved comment
        Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'content' => 'Одобренный комментарий для теста',
            'status' => Comment::STATUS_APPROVED,
        ]);

        // Create pending comment
        Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'content' => 'Ожидающий комментарий для теста',
            'status' => Comment::STATUS_PENDING,
        ]);

        $response = $this->getJson("/api/v1/articles/{$this->article->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }
}



