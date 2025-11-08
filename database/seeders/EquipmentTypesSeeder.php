<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EquipmentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipmentTypes = [
            ['name' => 'Laptop', 'slug' => 'laptop', 'description' => 'Portable computers and laptops'],
            ['name' => 'Desktop Computer', 'slug' => 'desktop', 'description' => 'Desktop computers and workstations'],
            ['name' => 'Tablet', 'slug' => 'tablet', 'description' => 'Tablet devices and iPads'],
            ['name' => 'Printer', 'slug' => 'printer', 'description' => 'Printers and printing devices'],
            ['name' => 'Scanner', 'slug' => 'scanner', 'description' => 'Document scanners and imaging devices'],
            ['name' => 'Projector', 'slug' => 'projector', 'description' => 'Video projectors and presentation equipment'],
            ['name' => 'Monitor', 'slug' => 'monitor', 'description' => 'Computer monitors and displays'],
            ['name' => 'Server', 'slug' => 'server', 'description' => 'Servers and server equipment'],
            ['name' => 'Router', 'slug' => 'router', 'description' => 'Network routers and routing equipment'],
            ['name' => 'Network Device', 'slug' => 'network', 'description' => 'Routers, switches, and network equipment'],
            ['name' => 'Audio Equipment', 'slug' => 'audio', 'description' => 'Speakers, microphones, and audio devices'],
            ['name' => 'Video Equipment', 'slug' => 'video', 'description' => 'Cameras, webcams, and video equipment'],
            ['name' => 'Other Equipment', 'slug' => 'other', 'description' => 'Miscellaneous equipment and devices'],
        ];

        foreach ($equipmentTypes as $index => $type) {
            DB::table('equipment_types')->insert([
                'name' => $type['name'],
                'slug' => $type['slug'],
                'description' => $type['description'],
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
