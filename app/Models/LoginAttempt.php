<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LoginAttempt extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'email',
        'successful',
        'attempted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    /**
     * Record a login attempt.
     */
    public static function recordAttempt(string $ipAddress, ?string $email, bool $successful): self
    {
        return self::create([
            'ip_address' => $ipAddress,
            'email' => $email,
            'successful' => $successful,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Check if IP is blocked (too many failed attempts).
     * 
     * Rule: More than 5 failed attempts in 1 minute = blocked for 15 minutes
     */
    public static function isIpBlocked(string $ipAddress): bool
    {
        // Check if there were more than 5 failed attempts in the last minute
        $recentFailures = self::where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinute())
            ->count();

        if ($recentFailures >= 5) {
            // Check if the last failed attempt was within 15 minutes
            $lastFailure = self::where('ip_address', $ipAddress)
                ->where('successful', false)
                ->orderByDesc('attempted_at')
                ->first();

            if ($lastFailure && $lastFailure->attempted_at->addMinutes(15)->isFuture()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if email is blocked (too many failed attempts).
     * 
     * Rule: More than 5 failed attempts in 5 minutes = blocked for 30 minutes
     */
    public static function isEmailBlocked(string $email): bool
    {
        // Check if there were more than 5 failed attempts in the last 5 minutes
        $recentFailures = self::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentFailures >= 5) {
            // Check if the last failed attempt was within 30 minutes
            $lastFailure = self::where('email', $email)
                ->where('successful', false)
                ->orderByDesc('attempted_at')
                ->first();

            if ($lastFailure && $lastFailure->attempted_at->addMinutes(30)->isFuture()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get remaining block time for IP in seconds.
     */
    public static function getIpBlockRemainingSeconds(string $ipAddress): int
    {
        $lastFailure = self::where('ip_address', $ipAddress)
            ->where('successful', false)
            ->orderByDesc('attempted_at')
            ->first();

        if (!$lastFailure) {
            return 0;
        }

        $unblockTime = $lastFailure->attempted_at->addMinutes(15);

        if ($unblockTime->isFuture()) {
            return now()->diffInSeconds($unblockTime);
        }

        return 0;
    }

    /**
     * Get remaining block time for email in seconds.
     */
    public static function getEmailBlockRemainingSeconds(string $email): int
    {
        $lastFailure = self::where('email', $email)
            ->where('successful', false)
            ->orderByDesc('attempted_at')
            ->first();

        if (!$lastFailure) {
            return 0;
        }

        $unblockTime = $lastFailure->attempted_at->addMinutes(30);

        if ($unblockTime->isFuture()) {
            return now()->diffInSeconds($unblockTime);
        }

        return 0;
    }

    /**
     * Clean old login attempts (older than 24 hours).
     */
    public static function cleanOldAttempts(): int
    {
        return self::where('attempted_at', '<', now()->subDay())->delete();
    }
}



