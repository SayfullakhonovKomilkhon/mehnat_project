<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Section;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\ChapterPolicy;
use App\Policies\CommentPolicy;
use App\Policies\SectionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Article::class => ArticlePolicy::class,
        Chapter::class => ChapterPolicy::class,
        Comment::class => CommentPolicy::class,
        Section::class => SectionPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for additional permissions
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('moderate-comments', function (User $user) {
            return $user->isAdminOrModerator();
        });

        Gate::define('manage-content', function (User $user) {
            return $user->isAdminOrModerator();
        });

        Gate::define('view-analytics', function (User $user) {
            return $user->isAdminOrModerator();
        });

        Gate::define('view-logs', function (User $user) {
            return $user->isAdmin();
        });
    }
}



