<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
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
        $user = auth()->user();

        // Add type hint for linter
        if ($user instanceof User) {
            // Allow administrators to bypass permission checks
            if ($user->is_admin) {
                return $next($request);
            }

            // Check if the user has the required permission
            if (!$user->hasPermissionTo($permission)) {
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
