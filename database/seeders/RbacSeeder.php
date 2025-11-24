<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing data
        DB::table('permission_role')->truncate();
        DB::table('role_user')->truncate();
        Permission::truncate();
        Role::truncate();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create permissions
        $permissions = [
            // User Management
            [
                'name' => 'users.view',
                'display_name' => 'View Users',
                'group' => 'Users',
                'description' => 'View user accounts',
                'is_active' => true,
            ],
            [
                'name' => 'users.create',
                'display_name' => 'Create Users',
                'group' => 'Users',
                'description' => 'Create new user accounts',
                'is_active' => true,
            ],
            [
                'name' => 'users.edit',
                'display_name' => 'Edit Users',
                'group' => 'Users',
                'description' => 'Edit existing user accounts',
                'is_active' => true,
            ],
            [
                'name' => 'users.delete',
                'display_name' => 'Delete Users',
                'group' => 'Users',
                'description' => 'Delete user accounts',
                'is_active' => true,
            ],

            // Role Management
            [
                'name' => 'roles.view',
                'display_name' => 'View Roles',
                'group' => 'Roles',
                'description' => 'View roles and their permissions',
                'is_active' => true,
            ],

            // Permission Management
            [
                'name' => 'permissions.view',
                'display_name' => 'View Permissions',
                'group' => 'Permissions',
                'description' => 'View all permissions',
                'is_active' => true,
            ],
            [
                'name' => 'permissions.edit',
                'display_name' => 'Edit Permissions',
                'group' => 'Permissions',
                'description' => 'Edit existing permissions',
                'is_active' => true,
            ],

            // Equipment Management
            [
                'name' => 'equipment.view',
                'display_name' => 'View Equipment',
                'group' => 'Equipment',
                'description' => 'View equipment listings',
                'is_active' => true,
            ],
            [
                'name' => 'equipment.create',
                'display_name' => 'Create Equipment',
                'group' => 'Equipment',
                'description' => 'Add new equipment',
                'is_active' => true,
            ],
            [
                'name' => 'equipment.edit',
                'display_name' => 'Edit Equipment',
                'group' => 'Equipment',
                'description' => 'Edit existing equipment',
                'is_active' => true,
            ],
            [
                'name' => 'equipment.delete',
                'display_name' => 'Delete Equipment',
                'group' => 'Equipment',
                'description' => 'Remove equipment',
                'is_active' => true,
            ],

            // Reports
            [
                'name' => 'reports.view',
                'display_name' => 'View Reports',
                'group' => 'Reports',
                'description' => 'View system reports',
                'is_active' => true,
            ],
            [
                'name' => 'reports.generate',
                'display_name' => 'Generate Reports',
                'group' => 'Reports',
                'description' => 'Generate and export reports',
                'is_active' => true,
            ],

            // Settings
            [
                'name' => 'settings.manage',
                'display_name' => 'Manage Settings',
                'group' => 'Settings',
                'description' => 'Update system settings',
                'is_active' => true,
            ],

            // Offices
            [
                'name' => 'offices.view',
                'display_name' => 'View Offices',
                'group' => 'Offices',
                'description' => 'View offices',
                'is_active' => true,
            ],
            [
                'name' => 'offices.create',
                'display_name' => 'Create Offices',
                'group' => 'Offices',
                'description' => 'Create new offices',
                'is_active' => true,
            ],
            [
                'name' => 'offices.edit',
                'display_name' => 'Edit Offices',
                'group' => 'Offices',
                'description' => 'Edit existing offices',
                'is_active' => true,
            ],

            // History
            [
                'name' => 'history.create',
                'display_name' => 'Create History',
                'group' => 'History',
                'description' => 'Creating equipment history entries',
                'is_active' => true,
            ],
            [
                'name' => 'history.store',
                'display_name' => 'Store History',
                'group' => 'History',
                'description' => 'Storing equipment history',
                'is_active' => true,
            ],
            [
                'name' => 'history.edit',
                'display_name' => 'Edit History',
                'group' => 'History',
                'description' => 'Edit equipment history entries',
                'is_active' => true,
            ],

            // QR Scan
            [
                'name' => 'qr.scan',
                'display_name' => 'QR Scan',
                'group' => 'QR',
                'description' => 'QR code scanning',
                'is_active' => true,
            ],

            // Profile
            [
                'name' => 'profile.view',
                'display_name' => 'View Profile',
                'group' => 'Profile',
                'description' => 'View user profiles',
                'is_active' => true,
            ],
            [
                'name' => 'profile.update',
                'display_name' => 'Update Profile',
                'group' => 'Profile',
                'description' => 'Update user profiles',
                'is_active' => true,
            ],

            // System Logs
            [
                'name' => 'system.logs.view',
                'display_name' => 'View System Logs',
                'group' => 'System',
                'description' => 'Viewing system logs',
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create roles
        $superAdminRole = Role::create([
            'name' => 'super-admin',
            'display_name' => 'Super Administrator',
            'description' => 'Super administrator with ultimate system access',
        ]);

        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System administrator with full access',
        ]);

        $technicianRole = Role::create([
            'name' => 'technician',
            'display_name' => 'Technician',
            'description' => 'Technical staff with limited access',
        ]);

        $staffRole = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'description' => 'Regular staff with basic access',
        ]);

        // Assign all permissions to super admin and admin roles
        $superAdminRole->permissions()->attach(Permission::all());
        $adminRole->permissions()->attach(Permission::all());

        // Assign permissions to technician role
        $technicianPermissions = Permission::whereIn('name', [
            'qr.scan',
            'equipment.view', 'equipment.edit', 'equipment.create',
            'reports.view', 'reports.generate',
            'profile.view', 'profile.update',
            'history.create', 'history.store', 'history.edit',
        ])->get();
        $technicianRole->permissions()->attach($technicianPermissions);

        // Assign permissions to staff role
        $staffPermissions = Permission::whereIn('name', [
            'qr.scan',
            'equipment.view', 'equipment.edit', 'equipment.create',
            'reports.view', 'reports.generate',
            'profile.view', 'profile.update',
        ])->get();
        $staffRole->permissions()->attach($staffPermissions);
    }
}
