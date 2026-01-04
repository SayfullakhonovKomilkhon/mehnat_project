<?php

use App\Models\LoginAttempt;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Clean old login attempts daily
Schedule::call(function () {
    LoginAttempt::cleanOldAttempts();
})->daily()->name('clean-login-attempts');

// Clear expired tokens weekly
Schedule::command('sanctum:prune-expired --hours=24')->weekly();

// Clear cache monthly (optional - can be triggered manually)
// Schedule::command('cache:clear')->monthly();



