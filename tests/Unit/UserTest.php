<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_role_relationship(): void
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test',
            'permissions' => [],
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertEquals('test', $user->role->slug);
    }

    public function test_user_has_role_check(): void
    {
        $role = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'permissions' => ['*'],
        ]);

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $this->assertTrue($user->hasRole(Role::ADMIN));
        $this->assertFalse($user->hasRole(Role::USER));
    }

    public function test_user_is_admin_check(): void
    {
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
            'permissions' => ['*'],
        ]);

        $userRole = Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => [],
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }

    public function test_two_factor_secret_encryption(): void
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => [],
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $secret = 'TESTSECRETKEY123';
        $user->setTwoFactorSecretEncrypted($secret);
        $user->save();

        // Reload user
        $user = $user->fresh();

        // Secret should be encrypted in database
        $this->assertNotEquals($secret, $user->two_factor_secret);

        // Decrypted secret should match original
        $this->assertEquals($secret, $user->getTwoFactorSecretDecrypted());
    }

    public function test_recovery_codes_encryption(): void
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => [],
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $codes = ['ABC123', 'DEF456', 'GHI789'];
        $user->setTwoFactorRecoveryCodesEncrypted($codes);
        $user->save();

        $user = $user->fresh();

        $decryptedCodes = $user->getTwoFactorRecoveryCodesDecrypted();
        $this->assertEquals($codes, $decryptedCodes);
    }

    public function test_use_recovery_code(): void
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => [],
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $codes = ['ABC123', 'DEF456', 'GHI789'];
        $user->setTwoFactorRecoveryCodesEncrypted($codes);
        $user->save();

        // Use a code
        $result = $user->useRecoveryCode('ABC123');
        $this->assertTrue($result);

        // Code should be removed
        $remainingCodes = $user->getTwoFactorRecoveryCodesDecrypted();
        $this->assertCount(2, $remainingCodes);
        $this->assertNotContains('ABC123', $remainingCodes);

        // Using same code again should fail
        $result = $user->useRecoveryCode('ABC123');
        $this->assertFalse($result);
    }

    public function test_has_two_factor_enabled(): void
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => Role::USER,
            'permissions' => [],
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $this->assertFalse($user->hasTwoFactorEnabled());

        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->assertTrue($user->hasTwoFactorEnabled());
    }
}



