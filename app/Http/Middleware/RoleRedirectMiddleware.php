<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class RoleRedirectMiddleware
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
        // Only apply to authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip AJAX requests and API calls
        if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
            return $next($request);
        }

        // Skip certain routes that don't need role-based redirects
        $currentRoute = $request->route();
        if ($currentRoute) {
            $routeName = $currentRoute->getName();
            // Skip logout, password reset, and other auth routes
            if (str_contains($routeName, 'logout') ||
                str_contains($routeName, 'password') ||
                str_contains($routeName, 'verification')) {
                return $next($request);
            }
        }

        $user = auth()->user();

        // Determine the correct prefix based on user's current roles
        $currentPrefix = $this->getCurrentRolePrefix($user);

        // Get the current route prefix from the URL
        $currentRoutePrefix = $this->getCurrentRoutePrefix($request);

        // If the prefixes don't match, force logout and redirect to login
        if ($currentPrefix && $currentRoutePrefix && $currentPrefix !== $currentRoutePrefix) {
            // Store SweetAlert data before logout
            session()->flash('swal', [
                'icon' => 'warning',
                'title' => 'Role Changed!',
                'text' => 'Your role has been changed by an administrator. Please login again with your new role.',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
            
            // Force logout for security
            if (auth()->check()) {
                auth()->logout();
                session()->invalidate();
                session()->regenerateToken();
            }
            
            return redirect()->route('login');
        }

        return $next($request);
    }

    /**
     * Get the current role prefix for the user
     */
    private function getCurrentRolePrefix($user)
    {
        if ($user->is_admin) {
            return 'admin';
        } elseif ($user->hasRole('technician')) {
            return 'technician';
        } elseif ($user->hasRole('staff')) {
            return 'staff';
        }

        return null;
    }

    /**
     * Get the current route prefix from the URL
     */
    private function getCurrentRoutePrefix(Request $request)
    {
        $path = $request->path();
        $segments = explode('/', $path);

        if (count($segments) >= 1) {
            $firstSegment = $segments[0];
            if (in_array($firstSegment, ['admin', 'technician', 'staff'])) {
                return $firstSegment;
            }
        }

        return null;
    }

    /**
     * Build the redirect URL with the new prefix
     */
    private function buildRedirectUrl(Request $request, $oldPrefix, $newPrefix)
    {
        $path = $request->path();
        $queryString = $request->getQueryString();

        // Replace the old prefix with the new one
        $newPath = str_replace("/{$oldPrefix}/", "/{$newPrefix}/", '/' . $path);

        // Build the full URL
        $url = $newPath;
        if ($queryString) {
            $url .= '?' . $queryString;
        }

        // Make sure the route exists before redirecting
        try {
            $routeExists = Route::getRoutes()->getByName(str_replace($oldPrefix, $newPrefix, $request->route()->getName()));
            if ($routeExists) {
                return $url;
            }
        } catch (\Exception $e) {
            // Route doesn't exist, don't redirect
        }

        // Fallback: redirect to the home page of the new role
        return "/{$newPrefix}";
    }
}