<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * List all users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $perPage = min($request->get('per_page', 20), 100);
        $roleId = $request->get('role_id');
        $isActive = $request->get('is_active');
        $search = $request->get('search');

        $query = User::with('role')
            ->orderByDesc('created_at');

        if ($roleId) {
            $query->where('role_id', $roleId);
        }

        if ($isActive !== null) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage);

        return $this->success([
            'items' => UserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Show a specific user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('view', $user);

        return $this->success(new UserResource($user));
    }

    /**
     * Update user role.
     *
     * @param UpdateUserRoleRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateRole(UpdateUserRoleRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('changeRole', $user);

        $role = Role::find($request->role_id);

        if (!$role) {
            return $this->error(__('messages.role_not_found'), 'ROLE_NOT_FOUND', 404);
        }

        $oldRoleId = $user->role_id;

        $user->update(['role_id' => $role->id]);

        // Log role change
        ActivityLog::log(
            ActivityLog::ACTION_CHANGE_ROLE,
            $request->user()->id,
            User::class,
            $user->id,
            ['role_id' => $oldRoleId],
            ['role_id' => $role->id],
            "User role changed from {$oldRoleId} to {$role->id}"
        );

        return $this->success(
            new UserResource($user->fresh()->load('role')),
            __('messages.user_role_updated')
        );
    }

    /**
     * Update user status (activate/deactivate).
     *
     * @param UpdateUserStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(UpdateUserStatusRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('changeStatus', $user);

        $oldStatus = $user->is_active;
        $newStatus = $request->is_active;

        $user->update(['is_active' => $newStatus]);

        // If deactivating, revoke all tokens
        if (!$newStatus) {
            $user->tokens()->delete();
        }

        // Log status change
        ActivityLog::log(
            $newStatus ? ActivityLog::ACTION_ACTIVATE_USER : ActivityLog::ACTION_DEACTIVATE_USER,
            $request->user()->id,
            User::class,
            $user->id,
            ['is_active' => $oldStatus],
            ['is_active' => $newStatus],
            $newStatus ? 'User activated' : 'User deactivated'
        );

        return $this->success(
            new UserResource($user->fresh()->load('role')),
            $newStatus ? __('messages.user_activated') : __('messages.user_deactivated')
        );
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('delete', $user);

        // Revoke all tokens
        $user->tokens()->delete();

        // Log deletion
        ActivityLog::logDelete($user, 'User deleted');

        // Soft delete
        $user->delete();

        return $this->success(null, __('messages.user_deleted'));
    }

    /**
     * Get all available roles.
     *
     * @return JsonResponse
     */
    public function roles(): JsonResponse
    {
        $roles = Role::all();

        return $this->success($roles);
    }
}



