<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admin can view any user
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admin can update any user (except other admins)
        if ($user->isAdmin()) {
            // Admin cannot demote another admin (safety measure)
            return !$model->isAdmin() || $user->id === $model->id;
        }

        return false;
    }

    /**
     * Determine whether the user can change the role of the model.
     */
    public function changeRole(User $user, User $model): bool
    {
        // Only admin can change roles
        if (!$user->isAdmin()) {
            return false;
        }

        // Admin cannot change their own role (safety)
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot modify other admins
        if ($model->isAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can change the status (active/inactive) of the model.
     */
    public function changeStatus(User $user, User $model): bool
    {
        // Only admin can change status
        if (!$user->isAdmin()) {
            return false;
        }

        // Admin cannot deactivate themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot deactivate other admins
        if ($model->isAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only admin can delete users
        if (!$user->isAdmin()) {
            return false;
        }

        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete other admins
        if ($model->isAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Never allow force delete of users in production
        return false;
    }
}



