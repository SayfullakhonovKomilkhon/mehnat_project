<?php

namespace Tests\Unit;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_has_all_permissions(): void
    {
        $role = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'permissions' => ['articles.create', 'articles.edit'],
        ]);

        // Admin should have any permission
        $this->assertTrue($role->hasPermission('anything'));
        $this->assertTrue($role->hasPermission('users.delete'));
    }

    public function test_role_permission_check(): void
    {
        $role = Role::create([
            'name' => 'Moderator',
            'slug' => Role::MODERATOR,
            'permissions' => ['comments.approve', 'comments.reject'],
        ]);

        $this->assertTrue($role->hasPermission('comments.approve'));
        $this->assertFalse($role->hasPermission('users.delete'));
    }

    public function test_role_type_checks(): void
    {
        $admin = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'permissions' => [],
        ]);

        $moderator = Role::create([
            'name' => 'Moderator',
            'slug' => Role::MODERATOR,
            'permissions' => [],
        ]);

        $user = Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => [],
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isModerator());
        $this->assertFalse($admin->isUser());

        $this->assertFalse($moderator->isAdmin());
        $this->assertTrue($moderator->isModerator());
        $this->assertFalse($moderator->isUser());

        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isModerator());
        $this->assertTrue($user->isUser());
    }
}



