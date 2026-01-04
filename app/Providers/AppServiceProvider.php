<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services
        $this->app->singleton(\App\Services\ArticleSearchService::class);
        $this->app->singleton(\App\Services\ChatbotService::class);
        $this->app->singleton(\App\Services\CommentModerationService::class);
        $this->app->singleton(\App\Services\StatisticsService::class);
        $this->app->singleton(\App\Services\TwoFactorAuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disable wrapping of JSON resources
        JsonResource::withoutWrapping();

        // Enable strict mode in development
        Model::shouldBeStrict(!$this->app->isProduction());

        // Log slow queries in development
        if (!$this->app->isProduction()) {
            DB::listen(function ($query) {
                if ($query->time > 1000) { // 1 second
                    Log::channel('slow_queries')->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                    ]);
                }
            });
        }

        // Force HTTPS in production
        if ($this->app->isProduction() && config('app.force_https', false)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}



