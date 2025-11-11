<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure optimized HTTP client for QR Server
        Http::macro('qrServer', function () {
            return Http::baseUrl('https://api.qrserver.com/v1')
                ->timeout(10)           // 10 second timeout
                ->connectTimeout(3)     // 3 second connect timeout
                ->retry(2, 100)         // Retry 2 times with 100ms delay
                ->withHeaders([
                    'User-Agent' => 'SDMD-Equipment-System/1.0',
                    'Accept' => 'image/png,image/svg+xml',
                ])
                ->withOptions([
                    'verify' => true,   // SSL verification
                    'http_version' => 1.1,
                ]);
        });

        // Custom Blade directive for permission checking
        Blade::if('can', function ($permission) {
            $user = auth('web')->user();
            return $user instanceof User && $user->hasPermissionTo($permission);
        });

        // Custom Blade directive for role checking
        Blade::if('hasRole', function ($role) {
            $user = auth('web')->user();
            return $user instanceof User && $user->hasRole($role);
        });

        // Custom Blade directive for admin check
        Blade::if('admin', function () {
            $user = auth('web')->user();
            return $user instanceof User && $user->is_admin;
        });
    }
}
