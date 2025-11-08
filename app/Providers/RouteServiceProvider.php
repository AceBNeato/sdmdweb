<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The path to redirect to after admin login.
     *
     * @var string
     */
    public const ADMIN_HOME = 'admin.accounts';

    /**
     * The path to redirect to after staff login.
     *
     * @var string
     */
    public const STAFF_HOME = 'staff.profile';

    /**
     * The path to redirect to after technician login.
     *
     * @var string
     */
    public const TECHNICIAN_HOME = 'technician.profile';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    protected function configureRateLimiting()
    {
        // Global rate limiting for API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login rate limiting
        RateLimiter::for('login', function (Request $request) {
            $key = 'login.' . strtolower($request->input('email')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key)->response(function() {
                return back()->with('error', 'Too many login attempts. Please try again later.');
            });
        });

        // Global rate limiting for web routes
        RateLimiter::for('web', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(1000)->by($request->user()->id)
                : Limit::perMinute(100)->by($request->ip());
        });
    }

    /**
     * Add security headers to all responses.
     *
     * @return void
     */
    protected function addSecurityHeaders()
    {
        if (app()->environment('production')) {
            $headers = [
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-Content-Type-Options' => 'nosniff',
                'X-XSS-Protection' => '1; mode=block',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
                'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
                'X-Permitted-Cross-Domain-Policies' => 'none',
            ];

            foreach ($headers as $key => $value) {
                header("{$key}: {$value}");
            }
        }
    }

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // API Routes
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            // Web Routes
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });

        $this->addSecurityHeaders();

        // Set the home route based on the authenticated user's role
        $this->setHomeRoute();
    }

    /**
     * Set the home route based on the authenticated user's role.
     *
     * @return void
     */
    protected function setHomeRoute(): void
    {
        $this->app->resolving('url', function ($url, $app) {
            if (auth()->check()) {
                $user = auth()->user();

                if ($user->position === 'Admin' || $user->is_admin) {
                    $this->app['config']->set('auth.home', self::ADMIN_HOME);
                } elseif ($user->position === 'Staff') {
                    $this->app['config']->set('auth.home', self::STAFF_HOME);
                } elseif ($user->position === 'Technician') {
                    $this->app['config']->set('auth.home', self::TECHNICIAN_HOME);
                }
            }
        });
    }
}
