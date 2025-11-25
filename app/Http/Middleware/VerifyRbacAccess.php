<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Activity;


class VerifyRbacAccess
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
        $user = auth()->user();

        // Check if user is authenticated first
        if (!$user) {
            Activity::logSystemManagement(
                'RBAC Access Redirect',
                'Unauthenticated user attempted to access RBAC management from ' . $request->path(),
                'rbac',
                null,
                [],
                [],
                'RBAC'
            );

            return redirect()->route('admin.accounts')
                ->with('error', 'Please log in to access this section.');
        }

        // Add type hint for linter
        if ($user instanceof User) {
            // Allow administrators to access RBAC
            if ($user->is_admin) {
                return $next($request);
            }

            // Deny access to non-admin users
            Activity::logSystemManagement(
                'RBAC Access Denied',
                'Non-admin user ' . $user->email . ' attempted to access RBAC management from ' . $request->path(),
                'rbac',
                null,
                [],
                [],
                'RBAC',
                $user
            );

            return redirect()->route('admin.accounts')
                ->with('error', 'You do not have permission to access RBAC management.');
        }

        return $next($request);
    }
}
