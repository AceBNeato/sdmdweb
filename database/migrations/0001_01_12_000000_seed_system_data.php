<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Campus;
use App\Models\Office;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create campuses
        $tagumCampus = Campus::create([
            'name' => 'Tagum',
            'address' => 'Tagum City, Davao del Norte',
            'contact_number' => '(084) 216-2374',
            'email' => 'tagum@sdmd.ph',
            'is_active' => true,
        ]);

        // Create offices
        $sdmdOffice = Office::create([
            'name' => 'SDMD Office',
            'location' => '2nd Floor, Admin Office',
            'contact_number' => '(084) 216-2374',
            'email' => 'admin@sdmd.ph',
            'campus_id' => $tagumCampus->id,
            'is_active' => true,
        ]);

        // Create categories
        $categories = [
            ['name' => 'Computer', 'description' => 'Desktop computers, laptops, and related equipment'],
            ['name' => 'Printer', 'description' => 'Printers and scanners'],
            ['name' => 'Network', 'description' => 'Routers, switches, and networking equipment'],
            ['name' => 'Server', 'description' => 'Server systems and related hardware'],
            ['name' => 'Storage', 'description' => 'External storage devices and media'],
            ['name' => 'Peripheral', 'description' => 'Keyboards, mice, monitors, and other peripherals'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }

        // Create equipment types
        $equipmentTypes = [
            ['name' => 'Desktop Computer', 'description' => 'Standard desktop computers'],
            ['name' => 'Laptop', 'description' => 'Portable laptop computers'],
            ['name' => 'Printer', 'description' => 'Various types of printers'],
            ['name' => 'Scanner', 'description' => 'Document scanners'],
            ['name' => 'Router', 'description' => 'Network routers'],
            ['name' => 'Switch', 'description' => 'Network switches'],
            ['name' => 'Server', 'description' => 'Server machines'],
            ['name' => 'Monitor', 'description' => 'Computer monitors'],
            ['name' => 'Keyboard', 'description' => 'Computer keyboards'],
            ['name' => 'Mouse', 'description' => 'Computer mice'],
        ];

        foreach ($equipmentTypes as $type) {
            \App\Models\EquipmentType::create($type);
        }


        // Create default settings
        $settings = [
            'app_name' => ['value' => 'SDMD System', 'type' => 'string', 'description' => 'Application name'],
            'app_version' => ['value' => '2.0.0', 'type' => 'string', 'description' => 'Application version'],
            'company_name' => ['value' => 'SDMD', 'type' => 'string', 'description' => 'Company name'],
            'company_address' => ['value' => 'Tagum City, Davao del Norte', 'type' => 'string', 'description' => 'Company address'],
            'company_email' => ['value' => 'admin@sdmd.ph', 'type' => 'string', 'description' => 'Company email'],
            'company_phone' => ['value' => '(084) 216-2374', 'type' => 'string', 'description' => 'Company phone'],
            'qr_code_prefix' => ['value' => 'SDMD-', 'type' => 'string', 'description' => 'QR code prefix'],
            'max_login_attempts' => ['value' => '5', 'type' => 'integer', 'description' => 'Maximum login attempts'],
            'session_timeout' => ['value' => '120', 'type' => 'integer', 'description' => 'Session timeout in minutes'],
        ];

        foreach ($settings as $key => $data) {
            \App\Models\Setting::create([
                'key' => $key,
                'value' => $data['value'],
                'type' => $data['type'],
                'description' => $data['description'],
                'is_public' => in_array($key, ['app_name', 'company_name']),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clean up seeded data
        User::truncate();
        \App\Models\Setting::truncate();
        \App\Models\EquipmentType::truncate();
        \App\Models\Category::truncate();
        Office::truncate();
        Campus::truncate();
    }
};
