<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create roles
        $roles = [
            ['name' => 'super-admin', 'display_name' => 'Super Administrator', 'description' => 'Full system access'],
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'System administration'],
            ['name' => 'technician', 'display_name' => 'Technician', 'description' => 'Equipment maintenance and repair'],
            ['name' => 'staff', 'display_name' => 'Staff', 'description' => 'Basic equipment access'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // Create permissions grouped by functionality
        $permissions = [
            // User management
            'users.view' => 'View Users',
            'users.create' => 'Create Users',
            'users.edit' => 'Edit Users',
            'users.delete' => 'Delete Users',
            
            // Role management
            'roles.view' => 'View Roles',
            'roles.create' => 'Create Roles',
            'roles.edit' => 'Edit Roles',
            'roles.delete' => 'Delete Roles',
            
            // Permission management
            'permissions.view' => 'View Permissions',
            'permissions.create' => 'Create Permissions',
            'permissions.edit' => 'Edit Permissions',
            'permissions.delete' => 'Delete Permissions',
            
            // Equipment management
            'equipment.view' => 'View Equipment',
            'equipment.create' => 'Create Equipment',
            'equipment.edit' => 'Edit Equipment',
            'equipment.delete' => 'Delete Equipment',
            'equipment.assign' => 'Assign Equipment',
            'equipment.unassign' => 'Unassign Equipment',
            'equipment.qr.generate' => 'Generate QR Code',
            
            // Equipment history
            'history.view' => 'View History',
            'history.create' => 'Create History',
            'history.store' => 'Store History',
            'history.edit' => 'Edit History',
            'history.delete' => 'Delete History',
            
            // Maintenance management
            'maintenance.view' => 'View Maintenance',
            'maintenance.create' => 'Create Maintenance',
            'maintenance.edit' => 'Edit Maintenance',
            'maintenance.delete' => 'Delete Maintenance',
            
            // Reports
            'reports.view' => 'View Reports',
            'reports.generate' => 'Generate Reports',
            'reports.export' => 'Export Reports',
            
            // Office management
            'offices.view' => 'View Offices',
            'offices.create' => 'Create Offices',
            'offices.edit' => 'Edit Offices',
            'offices.delete' => 'Delete Offices',
            
            // Category management
            'categories.view' => 'View Categories',
            'categories.create' => 'Create Categories',
            'categories.edit' => 'Edit Categories',
            'categories.delete' => 'Delete Categories',
            
            // Settings
            'settings.view' => 'View Settings',
            'settings.edit' => 'Edit Settings',
            
            // Activities
            'activities.view' => 'View Activities',
        ];

        foreach ($permissions as $name => $displayName) {
            $group = explode('.', $name)[0];
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'group' => ucfirst($group),
                'is_active' => true,
            ]);
        }

        // Assign permissions to roles
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $technicianRole = Role::where('name', 'technician')->first();
        $staffRole = Role::where('name', 'staff')->first();

        // Super Admin gets all permissions
        $allPermissionIds = Permission::pluck('id');
        $superAdminRole->permissions()->sync($allPermissionIds);

        // Admin gets most permissions (except super-admin specific)
        $adminPermissionIds = Permission::whereNotIn('name', ['users.delete'])->pluck('id');
        $adminRole->permissions()->sync($adminPermissionIds);

        // Technician gets equipment and maintenance permissions
        $technicianPermissionIds = Permission::whereIn('name', [
            'equipment.view', 'equipment.edit', 'equipment.qr.generate',
            'history.view', 'history.create', 'history.store', 'history.edit',
            'maintenance.view', 'maintenance.create', 'maintenance.edit',
            'reports.view', 'reports.generate',
            'activities.view'
        ])->pluck('id');
        $technicianRole->permissions()->sync($technicianPermissionIds);

        // Staff gets basic view permissions
        $staffPermissionIds = Permission::whereIn('name', [
            'equipment.view', 'history.view', 'reports.view', 'activities.view'
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPermissionIds);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all permissions and roles
        Permission::truncate();
        Role::truncate();
    }
};
