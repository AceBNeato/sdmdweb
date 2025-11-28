<?php

use App\Console\Commands\RunScheduledBackup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        RunScheduledBackup::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('backup:run --only-db')
            ->everyMinute()
            ->withoutOverlapping();
        $schedule->command('backup:clean')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\RoleRedirectMiddleware::class,
            \App\Http\Middleware\CheckPasswordChange::class,
        ]);

        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'rbac.verify' => \App\Http\Middleware\RbacVerification::class,
            'aggressive.back.prevent' => \App\Http\Middleware\AggressiveBackButtonPrevention::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
