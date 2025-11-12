<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  $roles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // Get user from the appropriate guard
        $user = Auth::user(); // web guard for admin
        if (!$user) {
            $user = Auth::guard('technician')->user();
        }
        if (!$user) {
            $user = Auth::guard('staff')->user();
        }
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // If no specific role is required, just check if user is authenticated
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // User doesn't have any of the required roles
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'You do not have permission to access this page.');
    }
}
