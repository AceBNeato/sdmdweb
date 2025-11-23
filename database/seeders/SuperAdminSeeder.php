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
            ['name' => 'Tagum'],
            [
                'address' => 'Tagum City, Davao del Norte',
                'contact_number' => '(084) 216-2374',
                'email' => 'tagum@sdmd.ph',
                'is_active' => true,
            ]
        );

        // Get or create offices (assuming they exist)
        $sdmdOffice = \App\Models\Office::firstOrCreate(
            ['name' => 'SDMD Office'],
            [
                'location' => '2nd Floor, Admin Office',
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
                'first_name' => 'Super',
                'last_name' => 'Administrator',
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
                'first_name' => 'Super',
                'last_name' => 'Administrator 2',
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
                'first_name' => 'Arthur',
                'last_name' => 'Dale Micaroz',
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

        // Assign single roles using role_id
        if ($superAdminRole) {
            $superAdmin->role_id = $superAdminRole->id;
            $superAdmin->save();
            $superAdmin2->role_id = $superAdminRole->id;
            $superAdmin2->save();
        }
        if ($adminRole) {
            $admin->role_id = $adminRole->id;
            $admin->save();
        }

        // Output summary
        $this->command->info('Created/Updated Users:');
        $this->command->info('- Super Admin 1: ' . $superAdmin->first_name . ' ' . $superAdmin->last_name . ' (' . $superAdmin->email . ') - ' . $superAdmin->position . ' at ' . $superAdmin->campus->name . ' (' . $sdmdOffice->name . ') - Password: superadmin123');
        $this->command->info('- Super Admin 2: ' . $superAdmin2->first_name . ' ' . $superAdmin2->last_name . ' (' . $superAdmin2->email . ') - ' . $superAdmin2->position . ' at ' . $superAdmin2->campus->name . ' (' . $sdmdOffice->name . ') - Password: superadmin123');
        $this->command->info('- Admin: ' . $admin->first_name . ' ' . $admin->last_name . ' (' . $admin->email . ') - ' . $admin->position . ' at ' . $admin->campus->name . ' (' . $sdmdOffice->name . ') - Password: 12345678');
    }
}
