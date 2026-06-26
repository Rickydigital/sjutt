<?php

use App\Http\Middleware\EnsureStudentIsElectionOfficer;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\MobileAuthMiddleware;
use App\Jobs\SendCalendarNotifications;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prependToGroup('api', ForceJsonResponse::class);

        // When an already-authenticated user hits a guest-only route,
        // send them to the right place based on which guard is active.
        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            if (Auth::guard('stuofficer')->check()) {
                return route('student.vote.index');
            }
            return route('dashboard');
        });

        $middleware->alias([
            'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'=> \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'mobile-auth'       => MobileAuthMiddleware::class,
            'officer'           => EnsureStudentIsElectionOfficer::class,
        ]);

        // Optional: apply globally if needed
        // $middleware->use([EnsureUserIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return null; // let default API handling take over
            }

            // stuofficer guard (students + election officers) → student login
            if (in_array('stuofficer', $e->guards())) {
                return redirect()->guest(route('stu.login'));
            }

            // all other guards (web, admin, etc.) → staff/admin login
            return redirect()->guest(route('login'));
        });
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