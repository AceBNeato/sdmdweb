<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Computer Hardware',
                'description' => 'Desktops, laptops, workstations, and computer components',
                'color' => '#2196F3',
                'icon' => 'fas fa-desktop',
                'is_active' => true,
            ],
            [
                'name' => 'Networking Equipment',
                'description' => 'Routers, switches, modems, cables, and network infrastructure',
                'color' => '#4CAF50',
                'icon' => 'fas fa-network-wired',
                'is_active' => true,
            ],
            [
                'name' => 'Servers & Storage',
                'description' => 'Servers, storage devices, NAS, and data center equipment',
                'color' => '#FF9800',
                'icon' => 'fas fa-server',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Devices',
                'description' => 'Tablets, smartphones, and mobile computing devices',
                'color' => '#9C27B0',
                'icon' => 'fas fa-mobile-alt',
                'is_active' => true,
            ],
            [
                'name' => 'Peripherals',
                'description' => 'Monitors, keyboards, mice, printers, and external devices',
                'color' => '#795548',
                'icon' => 'fas fa-keyboard',
                'is_active' => true,
            ],
            [
                'name' => 'Audio/Video Equipment',
                'description' => 'Projectors, speakers, microphones, cameras, and AV devices',
                'color' => '#E91E63',
                'icon' => 'fas fa-video',
                'is_active' => true,
            ],
            [
                'name' => 'Software & Licenses',
                'description' => 'Software applications, operating systems, and license keys',
                'color' => '#607D8B',
                'icon' => 'fas fa-code',
                'is_active' => true,
            ],
            [
                'name' => 'Security Equipment',
                'description' => 'Firewalls, antivirus software, surveillance cameras, and security tools',
                'color' => '#F44336',
                'icon' => 'fas fa-shield-alt',
                'is_active' => true,
            ],
            [
                'name' => 'Educational Technology',
                'description' => 'Interactive whiteboards, educational software, and learning tools',
                'color' => '#00BCD4',
                'icon' => 'fas fa-graduation-cap',
                'is_active' => true,
            ],
            [
                'name' => 'IT Maintenance Tools',
                'description' => 'Diagnostic tools, cable testers, and IT maintenance equipment',
                'color' => '#FF5722',
                'icon' => 'fas fa-tools',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('✅ Created ' . count($categories) . ' equipment categories');
    }
}
