<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userRole = Role::where('slug', Role::USER)->first();

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => $userRole?->id ?? 1,
            'is_active' => true,
            'preferred_locale' => fake()->randomElement(['uz', 'ru', 'en']),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            $adminRole = Role::where('slug', Role::ADMIN)->first();
            return [
                'role_id' => $adminRole?->id ?? 1,
            ];
        });
    }

    /**
     * Create a moderator user.
     */
    public function moderator(): static
    {
        return $this->state(function (array $attributes) {
            $moderatorRole = Role::where('slug', Role::MODERATOR)->first();
            return [
                'role_id' => $moderatorRole?->id ?? 2,
            ];
        });
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}



