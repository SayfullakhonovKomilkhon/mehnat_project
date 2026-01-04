<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new 2FA secret for a user.
     *
     * @param User $user
     * @return array
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey(32);
        
        // Store encrypted secret temporarily (not confirmed yet)
        $user->setTwoFactorSecretEncrypted($secret);
        $user->two_factor_confirmed_at = null;
        $user->save();

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('google2fa.issuer', 'Labor Code Portal'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    /**
     * Confirm 2FA setup.
     *
     * @param User $user
     * @param string $code
     * @return array
     */
    public function confirmSetup(User $user, string $code): array
    {
        $secret = $user->getTwoFactorSecretDecrypted();

        if (!$secret) {
            return [
                'success' => false,
                'message' => '2FA setup not initiated',
            ];
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey($secret, $code);

        if (!$valid) {
            return [
                'success' => false,
                'message' => 'Invalid verification code',
            ];
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Save confirmation
        $user->setTwoFactorRecoveryCodesEncrypted($recoveryCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Log the action
        ActivityLog::log(
            ActivityLog::ACTION_ENABLE_2FA,
            $user->id,
            User::class,
            $user->id,
            null,
            null,
            '2FA enabled'
        );

        return [
            'success' => true,
            'recovery_codes' => $recoveryCodes,
            'message' => '2FA has been enabled successfully',
        ];
    }

    /**
     * Disable 2FA for a user.
     *
     * @param User $user
     * @param string $code
     * @return array
     */
    public function disable(User $user, string $code): array
    {
        // Verify the code first
        if (!$this->verify($user, $code)) {
            return [
                'success' => false,
                'message' => 'Invalid verification code',
            ];
        }

        // Clear 2FA data
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        // Log the action
        ActivityLog::log(
            ActivityLog::ACTION_DISABLE_2FA,
            $user->id,
            User::class,
            $user->id,
            null,
            null,
            '2FA disabled'
        );

        return [
            'success' => true,
            'message' => '2FA has been disabled successfully',
        ];
    }

    /**
     * Verify a 2FA code.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verify(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecretDecrypted();

        if (!$secret) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $code, config('google2fa.window', 1));
    }

    /**
     * Verify a recovery code.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        return $user->useRecoveryCode($code);
    }

    /**
     * Generate new recovery codes.
     *
     * @return array
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        $count = config('google2fa.recovery_codes_count', 8);
        $length = config('google2fa.recovery_code_length', 10);

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random($length);
        }

        return $codes;
    }

    /**
     * Regenerate recovery codes.
     *
     * @param User $user
     * @param string $code
     * @return array
     */
    public function regenerateRecoveryCodes(User $user, string $code): array
    {
        // Verify the code first
        if (!$this->verify($user, $code)) {
            return [
                'success' => false,
                'message' => 'Invalid verification code',
            ];
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->setTwoFactorRecoveryCodesEncrypted($recoveryCodes);
        $user->save();

        return [
            'success' => true,
            'recovery_codes' => $recoveryCodes,
            'message' => 'Recovery codes regenerated successfully',
        ];
    }

    /**
     * Check if 2FA is required for a user.
     *
     * @param User $user
     * @return bool
     */
    public function isRequired(User $user): bool
    {
        // 2FA is required for admins
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get QR code as SVG.
     *
     * @param string $qrCodeUrl
     * @return string
     */
    public function getQrCodeSvg(string $qrCodeUrl): string
    {
        $writer = new \BaconQrCode\Writer(
            new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            )
        );

        return $writer->writeString($qrCodeUrl);
    }
}



