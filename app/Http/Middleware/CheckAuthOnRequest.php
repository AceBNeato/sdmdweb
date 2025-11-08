<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAuthOnRequest
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

        // If not authenticated, handle based on request type
        if (!$isAuthenticated) {
            if ($request->ajax() || $request->wantsJson()) {
                // For AJAX requests, return 401
                return response()->json(['message' => 'Unauthenticated.'], 401);
            } else {
                // For regular requests, redirect to login with cache prevention headers
                return redirect('/login')->withHeaders([
                    'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0'
                ]);
            }
        }

        return $next($request);
    }
}
