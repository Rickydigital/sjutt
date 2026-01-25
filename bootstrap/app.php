<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\MobileAuthMiddleware;
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
        $middleware->alias([
            'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'=> \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'mobile-auth'       => MobileAuthMiddleware::class,
        ]);

        // Optional: apply globally if needed
        // $middleware->use([EnsureUserIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {

        // 30-minute class reminders
        // Starts at 7:30 AM → sends reminder for 8:00 AM class
        // Runs every 30 minutes until 20:30 (covers up to 21:00 classes)
        $schedule->command('timetable:notify')
                 ->everyThirtyMinutes()
                 ->between('07:30', '20:30')
                 ->timezone('Africa/Dar_es_Salaam')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/timetable-notify.log'));

        // Daily Morning Motivation (Bible + Quran + encouragement)
        $schedule->command('motivation:morning')
                 ->dailyAt('07:00')
                 ->timezone('Africa/Dar_es_Salaam')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/morning-motivation.log'));

        $schedule->command('motivation:staff-morning')
                ->dailyAt('06:45')  // Slightly earlier so staff get it first
                ->timezone('Africa/Dar_es_Salaam')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/staff-morning-motivation.log'));

        // Your existing calendar notifications
        $schedule->job(new SendCalendarNotifications)
                 ->dailyAt('07:00')
                 ->timezone('Africa/Dar_es_Salaam');

        $schedule->command('lecturers:timetable-remind')
                ->everyThirtyMinutes()
                ->between('07:00', '20:30') 
                ->timezone('Africa/Dar_es_Salaam')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/lecturer-timetable-remind.log'));
    })
    ->create();