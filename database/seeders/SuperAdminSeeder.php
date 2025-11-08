<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create only the superadmin and admin users.
     */
    public function run(): void
    {
        // Get or create campuses (assuming they exist)
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

        // Get or create offices (assuming they exist)
        $sdmdOffice = \App\Models\Office::firstOrCreate(
            ['code' => 'SDMD'],
            [
                'name' => 'SDMD Office',
                'address' => '2nd Floor, Admin Office',
                'contact_number' => '(084) 216-2374',
                'email' => 'admin@sdmd.ph',
                'campus_id' => $tagumCampus->id,
                'is_active' => true,
            ]
        );

        // Create super admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@sdmd.ph'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('superadmin123'),
                'position' => 'Super Administrator',
                'phone' => '09123456780',
                'address' => 'Tagum City, Davao del Norte, Philippines',
                'office_id' => $sdmdOffice->id,
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
                'name' => 'Super Administrator 2',
                'password' => Hash::make('superadmin1234'),
                'position' => 'Super Administrator',
                'phone' => '09123456780',
                'address' => 'Tagum City, Davao del Norte, Philippines',
                'office_id' => $sdmdOffice->id,
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
                'name' => 'Arthur Dale Micaroz',
                'password' => Hash::make('12345678'),
                'position' => 'System Administrator',
                'phone' => '09123456789',
                'address' => 'Mankilam, Tagum City, Davao del Norte, Philippines',
                'office_id' => $sdmdOffice->id,
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

        // Assign roles
        if ($superAdminRole) {
            $superAdmin->roles()->sync([$superAdminRole->id]);
            $superAdmin2->roles()->sync([$superAdminRole->id]);
        }
        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }

        // Attach role permissions as direct permissions for users with roles
        $users = [$superAdmin, $superAdmin2, $admin];
        foreach ($users as $user) {
            if ($user->roles->isNotEmpty()) {
                foreach ($user->roles as $role) {
                    foreach ($role->permissions as $permission) {
                        $user->permissions()->syncWithoutDetaching([$permission->id => ['is_active' => true]]);
                    }
                }
            }
        }

        // Output summary
        $this->command->info('Created/Updated Users:');
        $this->command->info('- Super Admin 1: ' . $superAdmin->name . ' (' . $superAdmin->email . ') - ' . $superAdmin->position . ' at ' . $superAdmin->campus->name . ' (' . $sdmdOffice->name . ') - Password: superadmin123');
        $this->command->info('- Super Admin 2: ' . $superAdmin2->name . ' (' . $superAdmin2->email . ') - ' . $superAdmin2->position . ' at ' . $superAdmin2->campus->name . ' (' . $sdmdOffice->name . ') - Password: superadmin123');
        $this->command->info('- Admin: ' . $admin->name . ' (' . $admin->email . ') - ' . $admin->position . ' at ' . $admin->campus->name . ' (' . $sdmdOffice->name . ') - Password: 12345678');
    }
}
