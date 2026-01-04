<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return $this->success(new UserResource($user));
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $oldValues = $user->only(['name', 'phone', 'preferred_locale']);

        $user->update($request->validated());

        // Log the update
        ActivityLog::logUpdate($user, $oldValues, 'Profile updated');

        return $this->success(
            new UserResource($user->fresh()->load('role')),
            __('messages.profile_updated')
        );
    }

    /**
     * Change the authenticated user's password.
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all other tokens (keep current session)
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        // Log password change
        ActivityLog::log(
            ActivityLog::ACTION_PASSWORD_RESET,
            $user->id,
            \App\Models\User::class,
            $user->id,
            null,
            null,
            'Password changed by user'
        );

        return $this->success(null, __('messages.password_changed'));
    }
}



