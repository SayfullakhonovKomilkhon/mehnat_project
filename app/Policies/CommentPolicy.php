<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Comment $comment): bool
    {
        // Anyone can view approved comments
        if ($comment->status === Comment::STATUS_APPROVED) {
            return true;
        }

        // Admin/Moderator can view all comments
        if ($user && $user->isAdminOrModerator()) {
            return true;
        }

        // Author can view their own comments
        return $user && $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated and active user can create comments
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Admin/Moderator can edit any comment
        if ($user->isAdminOrModerator()) {
            return true;
        }

        // User can edit their own comment
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Admin can delete any comment
        if ($user->isAdmin()) {
            return true;
        }

        // User can delete their own comment
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can moderate (approve/reject) the model.
     */
    public function moderate(User $user, Comment $comment): bool
    {
        return $user->isAdminOrModerator();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }
}



