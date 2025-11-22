<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks to avoid issues with truncating
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncate tables to start fresh
        DB::table('permission_role')->truncate();
        DB::table('role_user')->truncate();
        Permission::truncate();
        Role::truncate();
        
        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create default roles
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System administrator with full access',
            'is_default' => false,
        ]);

        $technicianRole = Role::create([
            'name' => 'technician',
            'display_name' => 'Technician',
            'description' => 'Equipment maintenance technician',
            'is_default' => false,
        ]);

        $staffRole = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'description' => 'Regular staff member',
            'is_default' => true,
        ]);

        // Create permission groups
        $permissionGroups = [
            'users' => 'User Management',
            'roles' => 'Role Management',
            'equipment' => 'Equipment Management',
            'maintenance' => 'Maintenance Management',
            'history' => 'Equipment History',
            'reports' => 'Reports',
            'settings' => 'System Settings',
        ];

        // Create permissions
        $permissions = [
            // User permissions
            'users.view' => 'View Users',
            'users.create' => 'Create Users',
            'users.edit' => 'Edit Users',
            'users.delete' => 'Delete Users',
            'users.roles' => 'Manage User Roles',
            
            // Role permissions
            'roles.view' => 'View Roles',
            'roles.create' => 'Create Roles',
            'roles.edit' => 'Edit Roles',
            'roles.delete' => 'Delete Roles',
            'roles.permissions' => 'Manage Role Permissions',
            
            // Equipment permissions
            'equipment.view' => 'View Equipment',
            'equipment.create' => 'Add Equipment',
            'equipment.edit' => 'Edit Equipment',
            'equipment.delete' => 'Delete Equipment',
            'equipment.assigned' => 'View Assigned Equipment',
            'equipment.assign' => 'Assign Equipment',
            
            // Maintenance permissions
            'maintenance.view' => 'View Maintenance Logs',
            'maintenance.create' => 'Create Maintenance Logs',
            'maintenance.update' => 'Update Maintenance Logs',
            'maintenance.delete' => 'Delete Maintenance Logs',
            'maintenance.assign' => 'Assign Maintenance Tasks',
            'maintenance.complete' => 'Complete Maintenance Tasks',
            
            // History permissions
            'history.create' => 'Create History Records',
            'history.store' => 'Store History Records',
            'history.edit' => 'Edit History Records',
            'history.delete' => 'Delete History Records',
            
            // Report permissions
            'reports.view' => 'View Reports',
            'reports.generate' => 'Generate Reports',
            'reports.export' => 'Export Reports',
            
            // Settings permissions
            'settings.general' => 'Manage General Settings',
            'settings.system' => 'Manage System Settings',
        ];

        // Insert permissions
        foreach ($permissions as $name => $displayName) {
            $group = explode('.', $name)[0];
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $displayName,
                'group' => $permissionGroups[$group] ?? 'General',
            ]);
        }

        // Assign all permissions to admin role
        $adminRole->permissions()->attach(Permission::all());

        // Assign technician permissions
        $technicianPermissions = [
            'equipment.view',
            'equipment.assigned',
            'equipment.create',
            'equipment.edit',
            'maintenance.view',
            'maintenance.create',
            'maintenance.update',
            'maintenance.complete',
            'reports.view',
        ];
        $technicianRole->permissions()->attach(
            Permission::whereIn('name', $technicianPermissions)->pluck('id')
        );

        // Assign staff permissions
        $staffPermissions = [
            'equipment.view',
            'equipment.assigned',
            'equipment.create',
            'maintenance.view',
            'maintenance.create',
            'reports.view',
        ];
        $staffRole->permissions()->attach(
            Permission::whereIn('name', $staffPermissions)->pluck('id')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to do anything in the down method
        // as the migration will be rolled back automatically
    }
};
