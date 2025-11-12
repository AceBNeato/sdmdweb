<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Setting up SDMD System with Super Admin...');
        $this->command->info('==========================================');

        $this->call([
            RbacSeeder::class, // This creates roles and permissions first
            CampusesAndOfficesSeeder::class, // This creates campuses and offices first
            SuperAdminSeeder::class, // This creates only Super Admin and Admin users
            EquipmentTypesSeeder::class,
            CategorySeeder::class,
        ]);

        $this->command->info('✅ System setup complete!');
        $this->command->info('');
        $this->command->info('🔐 Super Admin Credentials:');
        $this->command->info('   Email: superadmin@sdmd.ph');
        $this->command->info('   Password: superadmin1234');
        $this->command->info('');
        $this->command->info('   Email: superadmin2@sdmd.ph');
        $this->command->info('   Password: superadmin1234');
        $this->command->info('');
        $this->command->info('👤 Admin Credentials:');
        $this->command->info('   Email: arthurdalemicaroz@gmail.com');
        $this->command->info('   Password: 12345678');
        $this->command->info('==========================================');

    }
}
