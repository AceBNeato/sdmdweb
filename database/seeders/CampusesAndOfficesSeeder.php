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

        // Sample offices data
        $offices = [
            // Tagum Campus Offices - Has IT and major departments
            [
                'name' => 'Office of the President',
                'code' => 'PRESIDENT',
                'campus_id' => $tagumId,
                'address' => 'Main Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'president@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Registrar\'s Office',
                'code' => 'REGISTRAR',
                'campus_id' => $tagumId,
                'address' => 'Administration Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'registrar@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
            [
                'name' => 'College of Information Technology',
                'code' => 'CIT',
                'campus_id' => $tagumId,
                'address' => 'CIT Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'cit@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'College of Engineering',
                'code' => 'COE',
                'campus_id' => $tagumId,
                'address' => 'Engineering Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'coe@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'College of Business Administration',
                'code' => 'CBA',
                'campus_id' => $tagumId,
                'address' => 'Business Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'cba@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'College of Education',
                'code' => 'COED',
                'campus_id' => $tagumId,
                'address' => 'Education Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'coed@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Library Services',
                'code' => 'LIBRARY',
                'campus_id' => $tagumId,
                'address' => 'Library Building, Tagum Campus',
                'contact_number' => '084-655-0452',
                'email' => 'library@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Mabini Campus Offices - No IT, has Agriculture and different departments
            [
                'name' => 'Campus Director\'s Office',
                'code' => 'DIRECTOR',
                'campus_id' => $mabiniId,
                'address' => 'Main Building, Mabini Campus',
                'contact_number' => '084-655-0452',
                'email' => 'mabini.director@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
            [
                'name' => 'College of Business and Management',
                'code' => 'CBM',
                'campus_id' => $mabiniId,
                'address' => 'Business Building, Mabini Campus',
                'contact_number' => '084-655-0452',
                'email' => 'cbm@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'College of Arts and Sciences',
                'code' => 'CAS',
                'campus_id' => $mabiniId,
                'address' => 'Arts Building, Mabini Campus',
                'contact_number' => '084-655-0452',
                'email' => 'cas@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'College of Industrial Technology',
                'code' => 'CIT-MABINI',
                'campus_id' => $mabiniId,
                'address' => 'Industrial Building, Mabini Campus',
                'contact_number' => '084-655-0452',
                'email' => 'cit.mabini@dnsc.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Research and Extension Office',
                'code' => 'RESEARCH',
                'campus_id' => $mabiniId,
                'address' => 'Research Building, Mabini Campus',
                'contact_number' => '084-655-0452',
                'email' => 'research@dnsc.edu.ph',
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
