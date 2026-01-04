<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\LoginAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Get the default user role
        $userRole = Role::where('slug', Role::USER)->first();

        if (!$userRole) {
            return $this->error(__('auth.role_not_found'), 'ROLE_NOT_FOUND', 500);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $userRole->id,
            'preferred_locale' => $request->preferred_locale ?? app()->getLocale(),
        ]);

        // Create API token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the registration
        ActivityLog::log(
            ActivityLog::ACTION_CREATE,
            $user->id,
            User::class,
            $user->id,
            null,
            ['name' => $user->name, 'email' => $user->email],
            'User registered'
        );

        return $this->created([
            'user' => new UserResource($user->load('role')),
            'token' => $token,
            'token_type' => 'Bearer',
        ], __('auth.registered'));
    }

    /**
     * Login a user.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $ip = $request->ip();
        $email = $request->email;

        // Check if IP is blocked
        if (LoginAttempt::isIpBlocked($ip)) {
            $remaining = LoginAttempt::getIpBlockRemainingSeconds($ip);
            return $this->error(
                __('auth.too_many_attempts', ['seconds' => $remaining]),
                'TOO_MANY_ATTEMPTS',
                429
            );
        }

        // Check if email is blocked
        if (LoginAttempt::isEmailBlocked($email)) {
            $remaining = LoginAttempt::getEmailBlockRemainingSeconds($email);
            return $this->error(
                __('auth.too_many_attempts', ['seconds' => $remaining]),
                'TOO_MANY_ATTEMPTS',
                429
            );
        }

        // Find user
        $user = User::where('email', $email)->first();

        // Verify password
        if (!$user || !Hash::check($request->password, $user->password)) {
            LoginAttempt::recordAttempt($ip, $email, false);
            
            ActivityLog::log(
                ActivityLog::ACTION_FAILED_LOGIN,
                null,
                null,
                null,
                null,
                ['email' => $email],
                'Failed login attempt'
            );

            return $this->error(__('auth.failed'), 'INVALID_CREDENTIALS', 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            LoginAttempt::recordAttempt($ip, $email, false);
            return $this->error(__('auth.account_deactivated'), 'ACCOUNT_DEACTIVATED', 403);
        }

        // Check 2FA if enabled
        if ($user->hasTwoFactorEnabled()) {
            // If 2FA code provided, verify it
            if ($request->two_factor_code) {
                if (!$this->twoFactorService->verify($user, $request->two_factor_code)) {
                    LoginAttempt::recordAttempt($ip, $email, false);
                    return $this->error(__('auth.invalid_2fa_code'), 'INVALID_2FA_CODE', 401);
                }
            } elseif ($request->recovery_code) {
                // Try recovery code
                if (!$this->twoFactorService->verifyRecoveryCode($user, $request->recovery_code)) {
                    LoginAttempt::recordAttempt($ip, $email, false);
                    return $this->error(__('auth.invalid_recovery_code'), 'INVALID_RECOVERY_CODE', 401);
                }
            } else {
                // 2FA required but not provided
                return $this->success([
                    'requires_2fa' => true,
                    'message' => __('auth.2fa_required'),
                ]);
            }
        }

        // Record successful login
        LoginAttempt::recordAttempt($ip, $email, true);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the login
        ActivityLog::log(
            ActivityLog::ACTION_LOGIN,
            $user->id,
            User::class,
            $user->id,
            null,
            null,
            'User logged in'
        );

        return $this->success([
            'user' => new UserResource($user->load('role')),
            'token' => $token,
            'token_type' => 'Bearer',
        ], __('auth.logged_in'));
    }

    /**
     * Logout the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete current token
        $request->user()->currentAccessToken()->delete();

        // Log the logout
        ActivityLog::log(
            ActivityLog::ACTION_LOGOUT,
            $user->id,
            User::class,
            $user->id,
            null,
            null,
            'User logged out'
        );

        return $this->success(null, __('auth.logged_out'));
    }

    /**
     * Send password reset link.
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, __('passwords.sent'));
        }

        return $this->error(__($status), 'PASSWORD_RESET_FAILED', 400);
    }

    /**
     * Reset password.
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all tokens
                $user->tokens()->delete();

                // Log password reset
                ActivityLog::log(
                    ActivityLog::ACTION_PASSWORD_RESET,
                    $user->id,
                    User::class,
                    $user->id,
                    null,
                    null,
                    'Password reset'
                );
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, __('passwords.reset'));
        }

        return $this->error(__($status), 'PASSWORD_RESET_FAILED', 400);
    }
}



