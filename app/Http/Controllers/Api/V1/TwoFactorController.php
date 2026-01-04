<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Enable 2FA - Generate secret and QR code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if already enabled
        if ($user->hasTwoFactorEnabled()) {
            return $this->error(__('auth.2fa_already_enabled'), '2FA_ALREADY_ENABLED', 400);
        }

        $result = $this->twoFactorService->generateSecret($user);

        // Generate SVG QR code
        $qrCodeSvg = $this->twoFactorService->getQrCodeSvg($result['qr_code_url']);

        return $this->success([
            'secret' => $result['secret'],
            'qr_code_url' => $result['qr_code_url'],
            'qr_code_svg' => base64_encode($qrCodeSvg),
        ], __('auth.2fa_setup_initiated'));
    }

    /**
     * Confirm 2FA setup.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        // Check if already confirmed
        if ($user->hasTwoFactorEnabled()) {
            return $this->error(__('auth.2fa_already_enabled'), '2FA_ALREADY_ENABLED', 400);
        }

        $result = $this->twoFactorService->confirmSetup($user, $request->code);

        if (!$result['success']) {
            return $this->error($result['message'], 'INVALID_CODE', 400);
        }

        return $this->success([
            'recovery_codes' => $result['recovery_codes'],
        ], $result['message']);
    }

    /**
     * Disable 2FA.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        // Check if 2FA is required for this user
        if ($this->twoFactorService->isRequired($user)) {
            return $this->error(__('auth.2fa_required_for_role'), '2FA_REQUIRED', 403);
        }

        // Check if enabled
        if (!$user->hasTwoFactorEnabled()) {
            return $this->error(__('auth.2fa_not_enabled'), '2FA_NOT_ENABLED', 400);
        }

        $result = $this->twoFactorService->disable($user, $request->code);

        if (!$result['success']) {
            return $this->error($result['message'], 'INVALID_CODE', 400);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Regenerate recovery codes.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->hasTwoFactorEnabled()) {
            return $this->error(__('auth.2fa_not_enabled'), '2FA_NOT_ENABLED', 400);
        }

        $result = $this->twoFactorService->regenerateRecoveryCodes($user, $request->code);

        if (!$result['success']) {
            return $this->error($result['message'], 'INVALID_CODE', 400);
        }

        return $this->success([
            'recovery_codes' => $result['recovery_codes'],
        ], $result['message']);
    }

    /**
     * Get 2FA status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'enabled' => $user->hasTwoFactorEnabled(),
            'required' => $this->twoFactorService->isRequired($user),
            'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
            'recovery_codes_count' => count($user->getTwoFactorRecoveryCodesDecrypted()),
        ]);
    }
}



