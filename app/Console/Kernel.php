<?php

namespace App\Console;

use App\Models\LoginAttempt;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Clean old login attempts daily
        $schedule->call(function () {
            LoginAttempt::cleanOldAttempts();
        })->daily()->name('clean-login-attempts');

        // Clear expired tokens weekly
        $schedule->command('sanctum:prune-expired --hours=24')->weekly();

        // Cache optimization weekly
        $schedule->command('cache:prune-stale-tags')->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}



