<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    /**
     * Role slugs constants
     */
    public const ADMIN = 'admin';
    public const MUALLIF = 'muallif';           // Author - writes content
    public const TARJIMON = 'tarjimon';         // Translator
    public const ISHCHI_GURUH = 'ishchi_guruh'; // Working Group - structure
    public const EKSPERT = 'ekspert';           // Expert - expert comments
    public const MODERATOR = 'moderator';       // Moderator
    public const USER = 'user';                 // Basic user

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get users that have this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Admin has all permissions
        if ($this->slug === self::ADMIN) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    /**
     * Check if this is the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->slug === self::ADMIN;
    }

    /**
     * Check if this is the moderator role.
     */
    public function isModerator(): bool
    {
        return $this->slug === self::MODERATOR;
    }

    /**
     * Check if this is the user role.
     */
    public function isUser(): bool
    {
        return $this->slug === self::USER;
    }

    /**
     * Check if this is the muallif (author) role.
     */
    public function isMuallif(): bool
    {
        return $this->slug === self::MUALLIF;
    }

    /**
     * Check if this is the tarjimon (translator) role.
     */
    public function isTarjimon(): bool
    {
        return $this->slug === self::TARJIMON;
    }

    /**
     * Check if this is the ishchi guruh (working group) role.
     */
    public function isIshchiGuruh(): bool
    {
        return $this->slug === self::ISHCHI_GURUH;
    }

    /**
     * Check if this is the ekspert (expert) role.
     */
    public function isEkspert(): bool
    {
        return $this->slug === self::EKSPERT;
    }
}



