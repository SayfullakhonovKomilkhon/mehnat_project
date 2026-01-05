<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'email_verified_at',
        'is_active',
        'preferred_locale',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
    ];

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get comments by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get comment likes by this user.
     */
    public function commentLikes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    /**
     * Get chatbot messages by this user.
     */
    public function chatbotMessages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class);
    }

    /**
     * Get activity logs for this user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $slug): bool
    {
        return $this->role->slug === $slug;
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Check if user is moderator.
     */
    public function isModerator(): bool
    {
        return $this->hasRole(Role::MODERATOR);
    }

    /**
     * Check if user is admin or moderator.
     */
    public function isAdminOrModerator(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    /**
     * Check if user is muallif (author).
     */
    public function isMuallif(): bool
    {
        return $this->hasRole(Role::MUALLIF);
    }

    /**
     * Check if user is tarjimon (translator).
     */
    public function isTarjimon(): bool
    {
        return $this->hasRole(Role::TARJIMON);
    }

    /**
     * Check if user is ishchi guruh (working group).
     */
    public function isIshchiGuruh(): bool
    {
        return $this->hasRole(Role::ISHCHI_GURUH);
    }

    /**
     * Check if user is ekspert (expert).
     */
    public function isEkspert(): bool
    {
        return $this->hasRole(Role::EKSPERT);
    }

    /**
     * Check if user can manage content.
     */
    public function canManageContent(): bool
    {
        return $this->isAdmin() || $this->isModerator() || $this->isIshchiGuruh();
    }

    /**
     * Check if user can create content.
     */
    public function canCreateContent(): bool
    {
        return $this->canManageContent() || $this->isMuallif();
    }

    /**
     * Check if user can translate content.
     */
    public function canTranslate(): bool
    {
        return $this->isAdmin() || $this->isTarjimon();
    }

    /**
     * Check if user can add expert comments.
     */
    public function canAddExpertComments(): bool
    {
        return $this->isAdmin() || $this->isEkspert();
    }

    /**
     * Check if 2FA is enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Get decrypted 2FA secret.
     */
    public function getTwoFactorSecretDecrypted(): ?string
    {
        if (!$this->two_factor_secret) {
            return null;
        }

        return Crypt::decryptString($this->two_factor_secret);
    }

    /**
     * Set encrypted 2FA secret.
     */
    public function setTwoFactorSecretEncrypted(string $secret): void
    {
        $this->two_factor_secret = Crypt::encryptString($secret);
    }

    /**
     * Get decrypted recovery codes.
     */
    public function getTwoFactorRecoveryCodesDecrypted(): array
    {
        if (!$this->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(Crypt::decryptString($this->two_factor_recovery_codes), true) ?? [];
    }

    /**
     * Set encrypted recovery codes.
     */
    public function setTwoFactorRecoveryCodesEncrypted(array $codes): void
    {
        $this->two_factor_recovery_codes = Crypt::encryptString(json_encode($codes));
    }

    /**
     * Use a recovery code.
     */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->getTwoFactorRecoveryCodesDecrypted();

        if (($key = array_search($code, $codes, true)) !== false) {
            unset($codes[$key]);
            $this->setTwoFactorRecoveryCodesEncrypted(array_values($codes));
            $this->save();
            return true;
        }

        return false;
    }
}



