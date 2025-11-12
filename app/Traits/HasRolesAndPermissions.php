<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasRolesAndPermissions
 * 
 * This trait provides role and permission functionality to the User model.
 */
trait HasRolesAndPermissions
{
    /**
     * A user may have multiple roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * A user may have multiple permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    /**
     * Check if the user has the given role.
     */
    public function hasRole($role): bool
    {
        // Get only active (non-expired) roles
        $activeRoles = $this->roles->filter(function($userRole) {
            $expiresAt = $userRole->pivot->expires_at;
            return is_null($expiresAt) || $expiresAt > now();
        });

        if (is_string($role)) {
            return $activeRoles->contains('name', $role);
        }

        if ($role instanceof Role) {
            return $activeRoles->contains('id', $role->id);
        }

        return (bool) $role->intersect($activeRoles)->count();
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole($roles): bool
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }
        return $this->hasRole($roles);
    }

    /**
     * Check if the user has all of the given roles.
     */
    public function hasAllRoles($roles): bool
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (!$this->hasRole($role)) {
                    return false;
                }
            }
            return true;
        }
        return $this->hasRole($roles);
    }

    /**
     * Check if the user has the given permission (direct or through roles).
     * Direct permissions can override role permissions:
     * - Active direct permission: grants access
     * - Inactive direct permission: denies access (even if role grants it)
     * - No direct permission: use role permissions
     */
    public function hasPermissionTo($permission): bool
    {
        // First check if there's a direct permission set for this user
        $directPermissionQuery = $this->permissions();

        if (is_string($permission)) {
            $directPermissionQuery->where('name', $permission);
        } elseif ($permission instanceof Permission) {
            $directPermissionQuery->where('id', $permission->id);
        }

        $directPermission = $directPermissionQuery->first();

        // Check if user has active admin role (super-admin or admin)
        $hasActiveAdminRole = $this->hasRole(['admin', 'super-admin']);

        // If direct permission exists
        if ($directPermission) {
            // Check the pivot is_active status
            $pivot = $directPermission->pivot;
            if ($pivot && $pivot->is_active) {
                return true; // Direct permission is active, grant access
            } elseif ($hasActiveAdminRole) {
                // User has active admin role, allow role permissions to override inactive direct permissions
                // Continue to role checking below
            } else {
                return false; // Direct permission is inactive and no admin role, deny access
            }
        }

        // No direct permission set, or direct permission is inactive but user has admin role
        // Check permissions through roles
        // Filter out expired roles
        $activeRoles = $this->roles->filter(function($role) {
            $expiresAt = $role->pivot->expires_at;
            return is_null($expiresAt) || $expiresAt > now();
        });

        return $activeRoles->contains(function($role) use ($permission) {
            // Check if role has the permission
            if (is_string($permission)) {
                return $role->permissions->contains('name', $permission);
            } elseif ($permission instanceof Permission) {
                return $role->permissions->contains('id', $permission->id);
            }
            return false;
        });
    }


    /**
     * Assign the given role to the user.
     */
    public function assignRole($role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
        
        return $this;
    }

    /**
     * Remove the given role from the user.
     */
    public function removeRole($role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        
        $this->roles()->detach($role);
        
        return $this;
    }

    /**
     * Sync the given roles.
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = [];
        
        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = Role::where('name', $role)->firstOrFail();
            }
            $roleIds[] = $role->id;
        }
        
        $this->roles()->sync($roleIds);
        
        return $this;
    }

    /**
     * Give the given permission to the user.
     */
    public function givePermissionTo($permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id => ['is_active' => true]]);
        
        return $this;
    }

    /**
     * Revoke the given permission from the user.
     */
    public function revokePermissionTo($permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission);
        
        return $this;
    }
}
