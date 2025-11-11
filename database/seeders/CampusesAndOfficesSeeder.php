<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampusesAndOfficesSeeder extends Seeder
{
    public function run(): void
    {
        // Insert campuses
        $campuses = [
            [
                'name' => 'Tagum',
                'code' => 'TAGUM',
                'address' => 'Apokon, Tagum City',
                'contact_number' => '084-655-0452',
                'email' => 'tagum@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mabini',
                'code' => 'MABINI',
                'address' => 'Mabini, Davao de Oro',
                'contact_number' => '084-655-0452',
                'email' => 'mabini@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Insert campuses and get their IDs
        $tagumCampus = DB::table('campuses')->where('name', 'Tagum')->first();
        $mabiniCampus = DB::table('campuses')->where('name', 'Mabini')->first();

        if (!$tagumCampus) {
            $tagumId = DB::table('campuses')->insertGetId($campuses[0]);
        } else {
            $tagumId = $tagumCampus->id;
        }

        if (!$mabiniCampus) {
            $mabiniId = DB::table('campuses')->insertGetId($campuses[1]);
        } else {
            $mabiniId = $mabiniCampus->id;
        }

        // Sample offices data - Reduced to 3 offices only
        $offices = [
            // SDMD Office - Tagum Campus
            [
                'name' => 'SDMD Office',
                'code' => 'SDMD',
                'campus_id' => $tagumId,
                'address' => 'SDMD Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'sdmd@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ICT Office - Tagum Campus
            [
                'name' => 'ICT Office',
                'code' => 'IT',
                'campus_id' => $tagumId,
                'address' => 'ICT Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'icto@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // College of Agriculture - Mabini Campus
            [
                'name' => 'College of Agriculture',
                'code' => 'COA',
                'campus_id' => $mabiniId,
                'address' => 'Agriculture Building, Mabini Campus',
                'contact_number' => '084-655-0452',
                'email' => 'coa@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert offices if they don't exist
        foreach ($offices as $office) {
            if (!DB::table('offices')
                ->where('name', $office['name'])
                ->where('campus_id', $office['campus_id'])
                ->exists()) {
                DB::table('offices')->insert($office);
            }
        }
    }
}
