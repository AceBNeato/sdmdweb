<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? ['web'] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Only redirect authenticated web users away from login/home
                if ($request->routeIs('login') || $request->routeIs('home')) {
                    if ($user->is_admin || $user->is_super_admin) {
                        return redirect()->route('admin.accounts');
                    }
                }
                
                // For API requests
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Already authenticated.'], 200);
                }
            }
        }

        return $next($request);
    }
}
