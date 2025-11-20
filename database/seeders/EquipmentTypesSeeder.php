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
            ['name' => 'Laptop'],
            ['name' => 'Desktop Computer'],
            ['name' => 'Tablet'],
            ['name' => 'Printer'],
            ['name' => 'Scanner'],
            ['name' => 'Projector'],
            ['name' => 'Monitor'],
            ['name' => 'Server'],
            ['name' => 'Router'],
            ['name' => 'Network Device'],
            ['name' => 'Audio Equipment'],
            ['name' => 'Video Equipment'],
            ['name' => 'Other Equipment'],
        ];

        foreach ($equipmentTypes as $type) {
            DB::table('equipment_types')->updateOrInsert(
                ['name' => $type['name']],
                [
                    'name' => $type['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
