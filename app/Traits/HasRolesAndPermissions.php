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
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        if ($role instanceof Role) {
            return $this->roles->contains('id', $role->id);
        }

        return (bool) $role->intersect($this->roles)->count();
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
     */
    public function hasPermissionTo($permission): bool
    {
        // First check direct permissions
        if (is_string($permission)) {
            $directPermission = $this->permissions()
                ->where('name', $permission)
                ->wherePivot('is_active', true)
                ->exists();

            if ($directPermission) {
                return true;
            }
        }

        if ($permission instanceof Permission) {
            $directPermission = $this->permissions()
                ->where('id', $permission->id)
                ->wherePivot('is_active', true)
                ->exists();

            if ($directPermission) {
                return true;
            }
        }

        // Check permissions through roles
        if (is_string($permission)) {
            return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })->exists();
        }

        if ($permission instanceof Permission) {
            return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
                $query->where('id', $permission->id);
            })->exists();
        }

        return false;
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
