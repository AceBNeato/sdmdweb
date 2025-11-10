<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the default admin, staff, and technician users.
     */
    public function run(): void
    {
        // Create or get roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'System Administrator with full access',
                'is_default' => false
            ]
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'staff'],
            [
                'display_name' => 'Staff',
                'description' => 'Regular staff with basic access',
                'is_default' => true
            ]
        );

        $technicianRole = Role::firstOrCreate(
            ['name' => 'technician'],
            [
                'display_name' => 'Technician',
                'description' => 'Technical staff with limited access',
                'is_default' => true
            ]
        );

        // Get or create campuses
        $tagumCampus = \App\Models\Campus::firstOrCreate(
            ['code' => 'TAGUM'],
            [
                'name' => 'Tagum',
                'address' => 'Tagum City, Davao del Norte',
                'contact_number' => '(084) 216-2374',
                'email' => 'tagum@sdmd.ph',
                'is_active' => true,
            ]
        );

        $mabiniCampus = \App\Models\Campus::firstOrCreate(
            ['code' => 'MABINI'],
            [
                'name' => 'Mabini',
                'address' => 'Mabini, Davao de Oro',
                'contact_number' => '(084) 216-2375',
                'email' => 'mabini@sdmd.ph',
                'is_active' => true,
            ]
        );

        // Get actual offices for user assignments (not campus-level)
        $presidentOffice = \App\Models\Office::where('code', 'PRESIDENT')->first();
        $registrarOffice = \App\Models\Office::where('code', 'REGISTRAR')->first();
        $itOffice = \App\Models\Office::where('code', 'IT')->first();
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@sdmd.ph'],
            [
                'first_name' => 'Super',
                'last_name' => 'Administrator',
                'password' => Hash::make('superadmin123'),
                'position' => 'Super Administrator',
                'phone' => '09123456780',
                'address' => 'Tagum City, Davao del Norte, Philippines',
                'office_id' => $presidentOffice->id,
                'campus_id' => $tagumCampus->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create second super admin user
        $superAdmin2 = User::updateOrCreate(
            ['email' => 'superadmin2@sdmd.ph'],
            [   
                'first_name' => 'Super',
                'last_name' => 'Administrator 2',
                'password' => Hash::make('superadmin123'),
                'position' => 'Super Administrator',
                'phone' => '09123456780',
                'address' => 'Tagum City, Davao del Norte, Philippines',
                'office_id' => $presidentOffice->id,
                'campus_id' => $tagumCampus->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'arthurdalemicaroz@gmail.com'],
            [
                'first_name' => 'Arthur',
                'last_name' => 'Dale Micaroz',
                'password' => Hash::make('12345678'),
                'position' => 'System Administrator',
                'phone' => '09123456789',
                'address' => 'Mankilam, Tagum City, Davao del Norte, Philippines',
                'office_id' => $presidentOffice->id,
                'campus_id' => $tagumCampus->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );






        // Get roles
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $staffRole = Role::where('name', 'staff')->first();
        $technicianRole = Role::where('name', 'technician')->first();

        // Assign roles
        if ($superAdminRole) {
            $superAdmin->roles()->sync([$superAdminRole->id]);
            $superAdmin2->roles()->sync([$superAdminRole->id]);
        }
        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }

        // Output summary
        $this->command->info('Created/Updated Users:');
        $this->command->info('- Super Admin 1: ' . $superAdmin->first_name . ' ' . $superAdmin->last_name . ' (' . $superAdmin->email . ') - ' . $superAdmin->position . ' at ' . $superAdmin->campus->name . ' - Password: superadmin123');
        $this->command->info('- Super Admin 2: ' . $superAdmin2->first_name . ' ' . $superAdmin2->last_name . ' (' . $superAdmin2->email . ') - ' . $superAdmin2->position . ' at ' . $superAdmin2->campus->name . ' - Password: superadmin123');
        $this->command->info('- Admin: ' . $admin->first_name . ' ' . $admin->last_name . ' (' . $admin->email . ') - ' . $admin->position . ' at ' . $admin->campus->name . ' - Password: 12345678');
    }
}
