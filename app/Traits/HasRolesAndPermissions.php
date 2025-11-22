<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasRolesAndPermissions
 * 
 * This trait provides role and permission functionality to the User model.
 * Updated for single role system.
 */
trait HasRolesAndPermissions
{
    /**
     * Get all permissions for the user through their role.
     */
    public function getAllPermissions()
    {
        if (!$this->role) {
            return collect();
        }

        return $this->role->permissions;
    }

    /**
     * Check if the user has the given role.
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->role?->name === $role;
        }

        if ($role instanceof Role) {
            return $this->role_id === $role->id;
        }

        return false;
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
     * Check if the user has the given permission through their role.
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_string($permission)) {
            return $this->getAllPermissions()->contains('name', $permission);
        }

        if ($permission instanceof Permission) {
            return $this->getAllPermissions()->contains('id', $permission->id);
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

        $this->role_id = $role->id;
        $this->save();
        
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

        if ($this->role_id === $role->id) {
            $this->role_id = null;
            $this->save();
        }
        
        return $this;
    }

    /**
     * Sync the given roles.
     */
    public function syncRoles(array $roles): self
    {
        // For single role system, only take the first role
        if (!empty($roles)) {
            $roleId = is_array($roles[0]) ? $roles[0]['id'] : $roles[0];
            $this->role_id = $roleId;
            $this->save();
        }
        
        return $this;
    }
}
