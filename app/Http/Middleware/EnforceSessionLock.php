<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class EnforceSessionLock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if any user is authenticated
        if (Auth::check() || Auth::guard('staff')->check() || Auth::guard('technician')->check()) {
            
            // Allow access to login, logout, unlock, heartbeat, and settings endpoints
            if ($request->is('*unlock-session*') || 
                $request->is('*logout*') || 
                $request->is('*login*') || 
                $request->is('*session-heartbeat*') ||
                $request->is('*session-settings*')) {
                return $next($request);
            }

            $lastActivity = session('last_activity');
            
            if (!$lastActivity) {
                $lastActivity = now();
                session(['last_activity' => $lastActivity]);
            } else if (is_numeric($lastActivity)) {
                $lastActivity = Carbon::createFromTimestamp($lastActivity);
            }

            $lockoutMinutes = Setting::getSessionLockoutMinutes();
            $inactiveMinutes = now()->diffInMinutes($lastActivity);
            
            \Log::info('EnforceSessionLock check', [
                'url' => $request->url(),
                'lastActivity' => $lastActivity,
                'inactiveMinutes' => $inactiveMinutes,
                'lockoutMinutes' => $lockoutMinutes,
                'isAjax' => $request->ajax()
            ]);

            if ($inactiveMinutes >= $lockoutMinutes) {
                if ($request->ajax() || $request->wantsJson() || $request->is('*session/check-status*') || $request->is('*system/check-updates*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session locked due to inactivity.',
                        'session_locked' => true
                    ], 403);
                }

                abort(403, 'Your session has been locked due to inactivity. Please refresh the page to unlock.');
            }

            // Update last activity for normal page loads
            // Do not update on AJAX/API requests as they might be automated background polling
            if (!$request->ajax() && !$request->wantsJson()) {
                // Also explicitly ignore background polling routes that might not send ajax headers
                if (!$request->is('*session/check-status*') && !$request->is('*system/check-updates*')) {
                    \Log::info('Updating last_activity', ['url' => $request->url()]);
                    session(['last_activity' => now()]);
                }
            }
        }

        return $next($request);
    }
}
