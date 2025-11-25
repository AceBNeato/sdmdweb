<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;

class RbacVerification
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
        // Only allow admins and super admins to access RBAC
        if (!Auth::check() || !Auth::user()->is_admin) {
            Activity::logSystemManagement(
                'RBAC Access Denied',
                'Unauthorized access attempt to RBAC management from ' . $request->path(),
                'rbac',
                null,
                [],
                [],
                'RBAC',
                Auth::user()
            );

            abort(403, 'Access denied. Only administrators can access RBAC management.');
        }

        // Additional security: Super admins can manage all roles including super-admin
        // Regular admins cannot manage super-admin role
        $user = Auth::user();
        $routeName = $request->route()->getName();
        
        // Prevent regular admins from managing super-admin role
        if (!$user->is_super_admin && $request->route('role')) {
            $role = $request->route('role');
            if ($role && $role->name === 'super-admin') {
                Activity::logSystemManagement(
                    'RBAC Super Admin Protection',
                    'Admin user ' . $user->email . ' attempted to manage super-admin role (ID: ' . $role->id . ') from ' . $request->path(),
                    'rbac',
                    $role->id,
                    [],
                    [],
                    'RBAC',
                    $user
                );

                abort(403, 'Access denied. Only super admins can manage the super-admin role.');
            }
        }

        return $next($request);
    }
}
