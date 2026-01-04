<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limit
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if ($user) {
                // Higher limits for authenticated users
                if ($user->isAdmin()) {
                    return Limit::perMinute(300)->by($user->id);
                }

                return Limit::perMinute(120)->by($user->id);
            }

            // Guest rate limit
            return Limit::perMinute(60)->by($request->ip());
        });

        // Authentication rate limits
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Password reset rate limits
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Registration rate limits
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Chatbot rate limits
        RateLimiter::for('chatbot', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}



