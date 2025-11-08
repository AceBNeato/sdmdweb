<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\AggressiveBackButtonPrevention::class,
        ]);

        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'prevent.back.cache' => \App\Http\Middleware\PreventBackButtonCache::class,
            'check.auth' => \App\Http\Middleware\CheckAuthOnRequest::class,
            'aggressive.back.prevent' => \App\Http\Middleware\AggressiveBackButtonPrevention::class,
            'ddos.protect' => \App\Http\Middleware\DDoSProtection::class,
            'honeypot.protect' => \App\Http\Middleware\HoneypotProtection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
