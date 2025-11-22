<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing permissions if they don't exist
        $permissions = [
            'history.edit' => 'Edit History Records',
            'history.delete' => 'Delete History Records',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate([
                'name' => $name,
            ], [
                'display_name' => $displayName,
                'description' => $displayName,
                'group' => 'history',
            ]);
        }

        // Assign history.edit permission to technician role if not already assigned
        $technicianRole = Role::where('name', 'technician')->first();
        $historyEditPermission = Permission::where('name', 'history.edit')->first();
        
        if ($technicianRole && $historyEditPermission && !$technicianRole->permissions->contains($historyEditPermission->id)) {
            $technicianRole->permissions()->syncWithoutDetaching([$historyEditPermission->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the permissions
        Permission::whereIn('name', ['history.edit', 'history.delete'])->delete();
    }
};
