<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only check for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user needs to change password (for all user models)
            if ($user && isset($user->must_change_password) && $user->must_change_password) {
                // Store session flag for SweetAlert (only if not already shown in this session)
                if (!session('must_change_password')) {
                    session(['must_change_password' => true]);
                }
            }
        }
        
        return $next($request);
    }
}
