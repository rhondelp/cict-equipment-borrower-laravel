<?php

use App\Http\Middleware\UserTypeMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'userType' => UserTypeMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Daily return reminders at 8:00 AM.
        // Note: previously everyMinute(), which would email borrowers repeatedly all day
        // and duplicate notification rows if schedule:run is registered per-minute.
        $schedule->command('notifications:return')->dailyAt('08:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
