<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AggressiveBackButtonPrevention
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated with any guard
        $isAuthenticated = false;

        if (Auth::check()) {
            $isAuthenticated = true;
        } elseif (Auth::guard('staff')->check()) {
            $isAuthenticated = true;
        } elseif (Auth::guard('technician')->check()) {
            $isAuthenticated = true;
        }

        // If accessing authenticated routes but not authenticated, create infinite redirect loop
        if (!$isAuthenticated && $this->isProtectedRoute($request)) {
            // Create infinite redirect loop by redirecting to login with timestamp
            return redirect('/login?blocked=' . time())->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0, no-transform, private, proxy-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Refresh' => '0; url=/login?blocked=' . time()
            ]);
        }

        $response = $next($request);

        // Add ultra-aggressive cache prevention headers to ALL responses
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, no-transform, private, proxy-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Check if the current route is protected (requires authentication)
     */
    private function isProtectedRoute(Request $request): bool
    {
        $path = $request->path();
        $method = $request->method();

        // Exclude login routes from protection to prevent redirect loops
        if (($method === 'POST' && in_array($path, ['technician/login', 'staff/login'])) ||
            ($method === 'GET' && $path === 'login')) {
            return false;
        }

        // Check if path starts with protected route prefixes
        $protectedPrefixes = [
            'admin/',
            'staff/',
            'technician/',
            'accounts/',
            'reports/',
            'system-logs/',
            'offices/',
            'maintenance/',
            'repairs/'
        ];

        foreach ($protectedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
