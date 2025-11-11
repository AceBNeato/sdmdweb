<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Allow admins to bypass email verification
        if ($user->is_admin) {
            return $next($request);
        }

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('email.verification.notice')
                ->with('warning', 'Please verify your email address before accessing this feature.');
        }

        return $next($request);
    }
}
