<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PreventGuardCrossAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $expectedGuard): Response
    {
        // Check if user is authenticated with the expected guard
        if (!Auth::guard($expectedGuard)->check()) {
            // Check if user is authenticated with a different guard
            $otherGuards = ['web', 'staff', 'technician'];
            
            foreach ($otherGuards as $guard) {
                if ($guard !== $expectedGuard && Auth::guard($guard)->check()) {
                    // User is logged in with wrong guard - logout and redirect
                    Auth::guard($guard)->logout();
                    $request->session()->regenerateToken();
                    
                    return redirect()->route('login')->with('error', 'Please login with the correct account type.');
                }
            }
            
            // User is not authenticated at all
            return redirect()->route('login');
        }
        
        return $next($request);
    }
}
