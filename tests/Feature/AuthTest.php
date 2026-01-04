<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => ['comments.create', 'comments.view'],
        ]);

        Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'permissions' => ['*'],
        ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!@',
            'password_confirmation' => 'Password123!@',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        $role = Role::where('slug', Role::USER)->first();

        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'Password123!@',
            'password_confirmation' => 'Password123!@',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_login(): void
    {
        $role = Role::where('slug', Role::USER)->first();

        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!@'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!@',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $role = Role::where('slug', Role::USER)->first();

        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!@'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $role = Role::where('slug', Role::USER)->first();

        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!@'),
            'role_id' => $role->id,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!@',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_logout(): void
    {
        $role = Role::where('slug', Role::USER)->first();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!@'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_registration_validation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'A', // Too short
            'email' => 'not-an-email',
            'password' => '123', // Too weak
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}



