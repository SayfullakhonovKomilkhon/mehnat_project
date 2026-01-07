<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\MuallifAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ArticlePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view articles
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Article $article): bool
    {
        // Anyone can view active articles
        if ($article->is_active) {
            return true;
        }

        // Only admin/moderator can view inactive articles
        return $user && $user->isAdminOrModerator();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdminOrModerator() || $user->isMuallif() || $user->isIshchiGuruh();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Article $article): bool
    {
        // Admin, moderator can update any article
        if ($user->isAdminOrModerator()) {
            return true;
        }
        
        // Translators can update any article (for translations)
        if ($user->isTarjimon()) {
            return true;
        }
        
        // Working group can update any article (for structure)
        if ($user->isIshchiGuruh()) {
            return true;
        }
        
        // Muallif can update any article (for now, until assignment system is fully set up)
        if ($user->isMuallif()) {
            return true;
        }
        
        // Ekspert can also update articles they're working on
        if ($user->isEkspert()) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Article $article): bool
    {
        // Only admin can delete
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Article $article): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Article $article): bool
    {
        return $user->isAdmin();
    }
}



