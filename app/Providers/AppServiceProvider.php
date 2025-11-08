<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
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
