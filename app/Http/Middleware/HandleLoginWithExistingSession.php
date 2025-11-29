<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleLoginWithExistingSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to login form submissions (POST requests)
        if ($request->isMethod('POST') && str_contains($request->path(), 'login')) {
            // Check if any user is already authenticated across all guards
            $existingUser = null;
            $existingGuard = null;
            $guards = ['web', 'staff', 'technician'];
            
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    $existingUser = Auth::guard($guard)->user();
                    $existingGuard = $guard;
                    break;
                }
            }
            
            // If someone is already logged in, redirect to their dashboard
            if ($existingUser) {
                $redirectRoute = $existingGuard === 'web' ? 'admin.qr-scanner' : 
                                ($existingGuard === 'staff' ? 'staff.equipment.index' : 'technician.qr-scanner');
                
                return redirect()->route($redirectRoute)->with('info', 
                    "User {$existingUser->name} is already logged in. Redirected to their dashboard.");
            }
        }
        
        return $next($request);
    }
}
