<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CleanupExpiredRoles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Clean up expired roles for authenticated users
        if (Auth::check()) {
            $user = Auth::user();

            // Log current roles before cleanup
            \Illuminate\Support\Facades\Log::info('CleanupExpiredRoles: Checking user ' . $user->email . ' for expired roles');
            
            // Debug: Log ALL roles with their expires_at values
            foreach ($user->roles as $role) {
                \Illuminate\Support\Facades\Log::info('CleanupExpiredRoles: Role found', [
                    'role_name' => $role->name,
                    'expires_at' => $role->pivot->expires_at,
                    'is_expired' => $role->pivot->expires_at && $role->pivot->expires_at <= now()
                ]);
            }

            // Remove expired ADMIN roles only (not all expired roles)
            $expiredRolesQuery = $user->roles()
                ->where('expires_at', '<=', now())
                ->whereNotNull('expires_at')
                ->where('name', 'admin'); // Only admin roles

            $expiredRoles = $expiredRolesQuery->get();
            if ($expiredRoles->isNotEmpty()) {
                \Illuminate\Support\Facades\Log::info('CleanupExpiredRoles: Removing expired ADMIN roles for user ' . $user->email, [
                    'expired_roles' => $expiredRoles->pluck('name')->toArray()
                ]);
                $expiredRolesQuery->detach();
                
                // Refresh the user's roles relationship to ensure updated data
                $user->load('roles');
                
                // Safeguard: Ensure technicians keep their technician role
                $hasTechnicianRole = $user->roles->contains('name', 'technician');
                if (!$hasTechnicianRole && $user->email !== 'admin@admin.com') { // Don't modify super admin
                    $technicianRole = \App\Models\Role::where('name', 'technician')->first();
                    if ($technicianRole) {
                        $user->roles()->attach($technicianRole->id, ['expires_at' => null]);
                        $user->load('roles'); // Refresh again
                        \Illuminate\Support\Facades\Log::info('CleanupExpiredRoles: Restored technician role for user ' . $user->email);
                    }
                }
                
                \Illuminate\Support\Facades\Log::info('CleanupExpiredRoles: Roles after cleanup for user ' . $user->email, [
                    'remaining_roles' => $user->roles->pluck('name')->toArray()
                ]);
            } else {
                \Illuminate\Support\Facades\Log::info('CleanupExpiredRoles: No expired admin roles found for user ' . $user->email);
            }

            // Clear any cached permissions to ensure fresh permission checks
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        }

        return $next($request);
    }
}
