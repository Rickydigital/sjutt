<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Jobs\SendCalendarNotifications;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        EnsureUserIsActive::class;
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('timetables:notify')->everyMinute();
        $schedule->job(new SendCalendarNotifications)->dailyAt('07:00');
    })
    ->create();
