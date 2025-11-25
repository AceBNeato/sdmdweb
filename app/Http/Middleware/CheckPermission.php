<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Temporary bypass for system-logs routes
        if (str_contains($request->path(), 'system-logs')) {
            return $next($request);
        }

        $user = auth()->user();

        // Add type hint for linter
        if ($user instanceof User) {
            // Allow administrators to bypass permission checks
            if ($user->is_admin) {
                return $next($request);
            }

            // Check if the user has the required permission
            if (!$user->hasPermissionTo($permission)) {
                Activity::logSystemManagement(
                    'RBAC Permission Denied',
                    'Permission "' . $permission . '" denied for user ' . $user->email . ' on ' . $request->path(),
                    'rbac',
                    null,
                    [],
                    [],
                    'RBAC',
                    $user
                );

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to perform this action.',
                    ], 403);
                }

                abort(403, 'You do not have permission to access this page.');
            }
        }

        return $next($request);
    }
}
