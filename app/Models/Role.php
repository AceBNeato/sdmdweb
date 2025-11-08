<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * The users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * The permissions that belong to the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * Assign a permission to the role.
     */
    public function givePermissionTo(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * Remove a permission from the role.
     */
    public function revokePermissionTo(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }

    /**
     * Check if the role has a specific permission.
     */
    public function hasPermissionTo(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }
}
