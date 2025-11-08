<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Office;
use App\Models\User;
use App\Models\Equipment;
use App\Models\MaintenanceLog;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ComprehensiveDataSeeder extends Seeder
{
    /**
     * Get a weighted random value from an array
     */
    public function getWeightedRandom($weightedValues)
    {
        $rand = mt_rand(1, (int) array_sum($weightedValues));

        foreach ($weightedValues as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }

        return array_key_last($weightedValues); // fallback
    }

    /**
     * Generate QR code for equipment using QRServer API (same as controller)
     */
    private function generateEquipmentQrCode($equipment)
    {
        try {
            // Generate QR code data - use equipment ID and basic info
            $qrData = "Equipment ID: {$equipment->id}\nModel: {$equipment->model_number}\nSerial: {$equipment->serial_number}\nType: {$equipment->equipment_type}\nLocation: {$equipment->location}";
            $qrSize = '200x200';

            // Use Laravel's QR code package directly (no external API)
            if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
                $qrCode = QrCode::format('png')
                    ->size(200)
                    ->generate($qrData);

                $fileName = 'equipment_' . $equipment->id . '.png';
                $path = 'qrcodes/' . $fileName;
                Storage::disk('public')->put($path, $qrCode);

                // Update equipment with QR code image path
                $equipment->update(['qr_code_image_path' => $path]);
            } else {
                // Fallback: just create a placeholder path without actual image
                $fileName = 'equipment_' . $equipment->id . '.png';
                $path = 'qrcodes/' . $fileName;
                $equipment->update(['qr_code_image_path' => $path]);
            }

        } catch (\Exception $e) {
            // Log error but don't fail the seeding
            $this->command->error('Failed to generate QR code for equipment ID: ' . $equipment->id . ' - ' . $e->getMessage());
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating comprehensive SDMD data...');

        // Create comprehensive campuses
        $campuses = [
            [
                'name' => 'Tagum Main Campus',
                'code' => 'TAGUM',
                'address' => 'Apokon Road, Tagum City, Davao del Norte 8100',
                'contact_number' => '(084) 216-2374',
                'email' => 'tagum.main@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mabini Campus',
                'code' => 'MABINI',
                'address' => 'National Highway, Poblacion, Mabini, Davao de Oro 8807',
                'contact_number' => '(084) 216-2375',
                'email' => 'mabini@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($campuses as $campus) {
            Campus::firstOrCreate(
                ['code' => $campus['code']],
                $campus
            );
        }

        // Get campus references
        $tagumCampus = Campus::where('code', 'TAGUM')->first();
        $mabiniCampus = Campus::where('code', 'MABINI')->first();

        // Create comprehensive offices for each campus
        $offices = [
            // Tagum Campus Offices
            [
                'name' => 'Office of the President',
                'code' => 'PRESIDENT',
                'campus_id' => $tagumCampus->id,
                'address' => 'Administration Building, 2nd Floor, Tagum Main Campus',
                'contact_number' => '(084) 216-2374',
                'email' => 'president@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Registrar\'s Office',
                'code' => 'REGISTRAR',
                'campus_id' => $tagumCampus->id,
                'address' => 'Administration Building, 1st Floor, Tagum Main Campus',
                'contact_number' => '(084) 216-2374',
                'email' => 'registrar@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Information Technology Department',
                'code' => 'IT',
                'campus_id' => $tagumCampus->id,
                'address' => 'ICT Building, Ground Floor, Tagum Main Campus',
                'contact_number' => '(084) 216-2374',
                'email' => 'it@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Human Resources Department',
                'code' => 'HR',
                'campus_id' => $tagumCampus->id,
                'address' => 'Administration Building, 3rd Floor, Tagum Main Campus',
                'contact_number' => '(084) 216-2374',
                'email' => 'hr@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance and Accounting Office',
                'code' => 'FINANCE',
                'campus_id' => $tagumCampus->id,
                'address' => 'Administration Building, 2nd Floor, Tagum Main Campus',
                'contact_number' => '(084) 216-2374',
                'email' => 'finance@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Library and Information Services',
                'code' => 'LIBRARY',
                'campus_id' => $tagumCampus->id,
                'address' => 'Library Building, Tagum Main Campus',
                'contact_number' => '(084) 216-2374',
                'email' => 'library@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Mabini Campus Offices
            [
                'name' => 'Campus Director\'s Office',
                'code' => 'DIRECTOR',
                'campus_id' => $mabiniCampus->id,
                'address' => 'Administration Building, 2nd Floor, Mabini Campus',
                'contact_number' => '(084) 216-2375',
                'email' => 'director.mabini@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Academic Affairs Office',
                'code' => 'ACADEMICS',
                'campus_id' => $mabiniCampus->id,
                'address' => 'Academic Building, 1st Floor, Mabini Campus',
                'contact_number' => '(084) 216-2375',
                'email' => 'academics.mabini@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Student Services Office',
                'code' => 'STUDENT',
                'campus_id' => $mabiniCampus->id,
                'address' => 'Student Center, Mabini Campus',
                'contact_number' => '(084) 216-2375',
                'email' => 'studentservices.mabini@sdmd.edu.ph',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ];

        foreach ($offices as $office) {
            Office::firstOrCreate(
                ['code' => $office['code']],
                $office
            );
        }

        $this->command->info('✅ Created campuses and offices');



        // Get all offices and staff for random assignment
        $offices = Office::all();
        $staff = User::where('is_staff', true)->get();
        $adminUser = User::where('email', 'arthurdalemicaroz@gmail.com')->first();

        // Equipment types and their details
        $equipmentTypes = [
            'laptop' => [
                'brands' => ['Dell', 'HP', 'Lenovo', 'Apple', 'Asus', 'Acer', 'Microsoft'],
                'models' => ['Latitude', 'EliteBook', 'ThinkPad', 'MacBook Pro', 'Zenbook', 'Swift', 'Surface'],
                'specs' => [
                    'Intel Core i7-1185G7, 16GB RAM, 512GB SSD, 14" FHD display, Windows 11 Pro',
                    'Intel Core i5-1135G7, 8GB RAM, 256GB SSD, 13.3" FHD display, Windows 11 Pro',
                    'AMD Ryzen 5 5500U, 16GB RAM, 512GB SSD, 15.6" FHD display, Windows 11 Pro',
                    'Apple M2, 16GB RAM, 512GB SSD, 13.3" Retina display, macOS Monterey',
                    'Intel Core i7-1165G7, 32GB RAM, 1TB SSD, 14" 4K display, Windows 11 Pro'
                ],
                'conditions' => ['excellent', 'good', 'fair'],
                'maintenance_days' => [180, 90]
            ],
            'desktop' => [
                'brands' => ['Dell', 'HP', 'Lenovo', 'Apple'],
                'models' => ['OptiPlex', 'EliteDesk', 'ThinkCentre', 'iMac'],
                'specs' => [
                    'Intel Core i7-11700, 32GB RAM, 1TB SSD, Windows 11 Pro',
                    'Intel Core i5-11500, 16GB RAM, 512GB SSD, Windows 11 Pro',
                    'AMD Ryzen 7 5700G, 16GB RAM, 512GB SSD, Windows 11 Pro',
                    'Intel Core i9-11900K, 64GB RAM, 2TB SSD, Windows 11 Pro'
                ],
                'conditions' => ['excellent', 'good', 'fair'],
                'maintenance_days' => [180, 120]
            ],
            'printer' => [
                'brands' => ['Canon', 'HP', 'Brother', 'Epson'],
                'models' => ['imageCLASS', 'LaserJet', 'HL-L', 'WorkForce'],
                'specs' => [
                    'Multifunction laser printer, 40ppm, Duplex, Network ready, 250-sheet tray',
                    'Color laser printer, 30ppm color, Wireless/Network connectivity, 250-sheet capacity',
                    'Monochrome laser printer, 35ppm, Duplex printing, USB/Network connectivity',
                    'Inkjet multifunction printer, 20ppm color, WiFi/USB connectivity, 150-sheet tray'
                ],
                'conditions' => ['excellent', 'good', 'fair', 'poor'],
                'maintenance_days' => [90, 60]
            ],
            'projector' => [
                'brands' => ['Epson', 'BenQ', 'Optoma', 'Sony'],
                'models' => ['PowerLite', 'MH', 'HD', 'VPL'],
                'specs' => [
                    'WXGA laser projector, 5200 lumens, 1280x800 resolution, 20,000 hour laser life',
                    'Full HD projector, 3800 lumens, 1920x1080 resolution, 15,000 hour lamp life',
                    '4K UHD projector, 3000 lumens, 3840x2160 resolution, 20,000 hour laser life',
                    'Portable projector, 3200 lumens, WXGA resolution, 10,000 hour lamp life'
                ],
                'conditions' => ['excellent', 'good', 'fair'],
                'maintenance_days' => [90, 60]
            ],
            'network' => [
                'brands' => ['Cisco', 'TP-Link', 'Netgear', 'Ubiquiti'],
                'models' => ['Catalyst', 'Archer', 'GS', 'UniFi'],
                'specs' => [
                    '24-port managed switch, Gigabit Ethernet, 2x SFP uplinks, Layer 2/3 features',
                    'Wireless router, AC1200 dual-band, Gigabit ports, MU-MIMO, Parental controls',
                    '48-port PoE switch, Gigabit Ethernet, 4x SFP+ uplinks, Layer 3 features',
                    'Wireless access point, WiFi 6, MU-MIMO, 2.5G Ethernet port, PoE powered'
                ],
                'conditions' => ['excellent', 'good'],
                'maintenance_days' => [90, 60]
            ],
            'server' => [
                'brands' => ['Dell', 'HP', 'Supermicro', 'Lenovo'],
                'models' => ['PowerEdge', 'ProLiant', 'SuperServer', 'ThinkSystem'],
                'specs' => [
                    'Rack server, Intel Xeon Silver 4314, 128GB RAM, 4x 2TB SSD RAID, Windows Server 2022',
                    'Tower server, Intel Xeon Gold 6248, 256GB RAM, 8x 4TB HDD RAID, Windows Server 2022',
                    'Blade server, AMD EPYC 7742, 512GB RAM, 16x 1TB NVMe SSD, Linux Ubuntu Server',
                    'Storage server, Intel Xeon Bronze 3204, 64GB RAM, 24x 8TB HDD RAID, Windows Server 2022'
                ],
                'conditions' => ['excellent', 'good'],
                'maintenance_days' => [90, 60]
            ],
            'monitor' => [
                'brands' => ['Dell', 'HP', 'LG', 'Samsung'],
                'models' => ['UltraSharp', 'EliteDisplay', 'UltraFine', 'ViewFinity'],
                'specs' => [
                    '27" 4K UHD monitor, IPS panel, 3840x2160 resolution, USB-C connectivity',
                    '24" Full HD monitor, IPS panel, 1920x1080 resolution, HDMI/DisplayPort',
                    '32" WQHD monitor, IPS panel, 2560x1440 resolution, USB-C/HDMI connectivity',
                    '22" Full HD monitor, TN panel, 1920x1080 resolution, VGA/DVI connectivity'
                ],
                'conditions' => ['excellent', 'good', 'fair'],
                'maintenance_days' => [365, 180]
            ],
            'tablet' => [
                'brands' => ['Apple', 'Samsung', 'Microsoft', 'Lenovo'],
                'models' => ['iPad Pro', 'Galaxy Tab', 'Surface Pro', 'Tab P'],
                'specs' => [
                    '12.9" iPad Pro, Apple M2 chip, 256GB storage, WiFi + Cellular, iPadOS',
                    '11" Galaxy Tab S9, Snapdragon 8 Gen 2, 256GB storage, 5G connectivity, Android',
                    '13" Surface Pro 9, Intel Core i7, 512GB SSD, Windows 11, detachable keyboard',
                    '10.6" Tab P12 Pro, MediaTek Kompanio 1300T, 128GB storage, Android 12'
                ],
                'conditions' => ['excellent', 'good'],
                'maintenance_days' => [180, 90]
            ],
            'scanner' => [
                'brands' => ['Canon', 'Epson', 'Brother', 'Fujitsu'],
                'models' => ['imageFORMULA', 'Perfection', 'ADS', 'ScanSnap'],
                'specs' => [
                    'Document scanner, 60ppm duplex, 100-sheet ADF, USB/Network connectivity',
                    'Photo scanner, 6400dpi resolution, 48-bit color depth, USB 3.0 connectivity',
                    'Portable scanner, 20ppm color, 600dpi resolution, USB powered',
                    'High-speed scanner, 80ppm duplex, 200-sheet ADF, Gigabit Ethernet'
                ],
                'conditions' => ['excellent', 'good', 'fair'],
                'maintenance_days' => [90, 60]
            ],
            'ups' => [
                'brands' => ['APC', 'CyberPower', 'Tripp Lite', 'Eaton'],
                'models' => ['Smart-UPS', 'PFC Sinewave', 'SMART', '5PX'],
                'specs' => [
                    '1500VA/900W UPS, Line-interactive, 8 outlets, LCD display, USB connectivity',
                    '2200VA/1600W UPS, Pure sine wave, 10 outlets, Network card, Extended runtime',
                    '1000VA/600W UPS, Line-interactive, 6 outlets, USB/Serial connectivity',
                    '3000VA/2700W UPS, Online double-conversion, 8 outlets, SNMP card, Extended runtime'
                ],
                'conditions' => ['excellent', 'good'],
                'maintenance_days' => [180, 90]
            ]
        ];

        // Generate 50+ equipment items distributed across offices
        $equipmentData = [];
        $maintenanceData = []; // Store maintenance info for each equipment
        $equipmentCount = 0;

        // Distribute equipment evenly across offices instead of random assignment
        $officesArray = $offices->toArray();
        $equipmentPerOffice = floor(60 / count($officesArray)); // Distribute equipment evenly
        $extraEquipment = 60 % count($officesArray); // Handle remainder

        foreach ($officesArray as $officeIndex => $officeData) {
            $office = $offices->find($officeData['id']);
            $officeEquipmentCount = $equipmentPerOffice + ($officeIndex < $extraEquipment ? 1 : 0);

            for ($i = 0; $i < $officeEquipmentCount && $equipmentCount < 60; $i++) {
                $type = array_rand($equipmentTypes);
                $typeData = $equipmentTypes[$type];
                $brand = $typeData['brands'][array_rand($typeData['brands'])];
                $model = $typeData['models'][array_rand($typeData['models'])];
                $spec = $typeData['specs'][array_rand($typeData['specs'])];
                $condition = $typeData['conditions'][array_rand($typeData['conditions'])];
                $maintenanceInterval = $typeData['maintenance_days'][array_rand($typeData['maintenance_days'])];

                // Set office and campus based assignment
                $officeName = $office->name;
                $campusId = $office->campus_id;
                $department = match($office->code) {
                    'PRESIDENT' => 'Administration',
                    'REGISTRAR' => 'Student Services',
                    'IT' => 'Information Technology',
                    'HR' => 'Human Resources',
                    'FINANCE' => 'Finance',
                    'LIBRARY' => 'Library Services',
                    'DIRECTOR' => 'Administration',
                    'ACADEMICS' => 'Academic Affairs',
                    'STUDENT' => 'Student Services',
                    'ADMIN' => 'Administration',
                    'LRC' => 'Learning Resources',
                    default => 'General'
                };

                // Generate location based on office
                $locations = [
                    'Office Workstation',
                    'Conference Room',
                    'Computer Lab',
                    'Library Area',
                    'Server Room',
                    'Print Station',
                    'Meeting Room',
                    'Faculty Office',
                    'Student Area',
                    'IT Closet'
                ];
                $location = $officeName . ' - ' . $locations[$i % count($locations)];

                // Random purchase date (within last 2 years)
                $purchaseDate = now()->subDays(rand(30, 730))->format('Y-m-d');

                // All equipment should be assigned to staff
                $status = 'assigned';

                // Random notes
                $notesOptions = [
                    'Standard office equipment',
                    'Available for general use',
                    'Reserved for department use',
                    'Under regular maintenance',
                    'Recently upgraded',
                    'High-performance model',
                    'Energy-efficient model',
                    'Portable equipment'
                ];
                $notes = $notesOptions[array_rand($notesOptions)];

                // Calculate maintenance dates for maintenance log creation later
                $lastMaintenance = now()->subDays(rand(30, $maintenanceInterval));
                $nextMaintenance = $lastMaintenance->copy()->addDays($maintenanceInterval);

                $equipmentData[] = [
                    'model_number' => $brand . '-' . strtoupper(substr($type, 0, 3)) . '-' . str_pad($equipmentCount + 1, 3, '0', STR_PAD_LEFT),
                    'serial_number' => strtoupper($brand) . rand(100000000, 999999999),
                    'equipment_type' => $type,
                    'description' => $brand . ' ' . $model . ' - ' . $spec,
                    'location' => $location,
                    'department' => $department,
                    'purchase_date' => $purchaseDate,
                    'campus_id' => $campusId,
                    'office_id' => $office->id,
                    'qr_code' => 'EQP-' . strtoupper(Str::random(8)),
                    'qr_code_image_path' => 'qrcodes/equipment_' . ($equipmentCount + 1) . '.png',
                    'status' => $status,
                    'condition' => $condition,
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Store maintenance data for later use
                $maintenanceData[$equipmentCount] = [
                    'last_maintenance' => $lastMaintenance,
                    'next_maintenance' => $nextMaintenance,
                    'interval_days' => $maintenanceInterval,
                ];

                $equipmentCount++;
            }
        }

        // Create equipment assignments for ALL equipment items
        $assignments = [];
        $staffList = $staff->toArray(); // Convert to array for easier manipulation
        $staffIndex = 0;

        foreach ($equipmentData as $index => $equipment) {
            // Find staff in the same office, or use any staff if none in same office
            $officeStaff = $staff->where('office_id', $equipment['office_id']);

            if ($officeStaff->count() > 0) {
                // Use staff from the same office
                $selectedStaff = $officeStaff->random();
            } else {
                // Fallback to any available staff (shouldn't happen with proper setup)
                $selectedStaff = $staff->random();
            }

            $assignments[] = [
                'equipment_id' => $index + 1, // Will be updated after creation
                'assigned_to_type' => User::class,
                'assigned_to_id' => $selectedStaff->id,
                'assigned_by_type' => User::class,
                'assigned_by_id' => $adminUser ? $adminUser->id : 1,
                'assigned_at' => now()->subDays(rand(1, 60)),
                'notes' => 'Assigned for ' . strtolower($equipment['department']) . ' work in ' . $equipment['location'],
            ];
        }

        foreach ($equipmentData as $equipment) {
            Equipment::firstOrCreate(
                ['serial_number' => $equipment['serial_number']],
                $equipment
            );
        }

        $this->command->info('✅ Created equipment');

        // Generate QR codes for all equipment
        $createdEquipment = Equipment::all();
        foreach ($createdEquipment as $equipment) {
            // Just create QR code path - actual QR code will be generated on-demand by controller
            $fileName = 'equipment_' . $equipment->id . '.png';
            $path = 'qrcodes/' . $fileName;
            $equipment->update(['qr_code_image_path' => $path]);
        }

        $this->command->info('✅ Generated QR code paths for equipment');

        // Create maintenance logs for equipment
        $createdEquipment = Equipment::all();
        foreach ($createdEquipment as $index => $equipment) {
            if (isset($maintenanceData[$index])) {
                $maintData = $maintenanceData[$index];

                // Create initial maintenance log entry
                \App\Models\MaintenanceLog::create([
                    'equipment_id' => $equipment->id,
                    'user_id' => $adminUser ? $adminUser->id : 1, // Use admin user or first user
                    'maintenance_type' => \App\Models\MaintenanceLog::TYPE_SCHEDULED,
                    'description' => 'Initial scheduled maintenance setup',
                    'status' => \App\Models\MaintenanceLog::STATUS_COMPLETED,
                    'scheduled_date' => $maintData['last_maintenance']->format('Y-m-d'),
                    'completed_date' => $maintData['last_maintenance']->format('Y-m-d'),
                    'priority' => \App\Models\MaintenanceLog::PRIORITY_MEDIUM,
                    'work_performed' => 'Initial setup and configuration',
                    'recommendations' => 'Continue regular maintenance every ' . $maintData['interval_days'] . ' days',
                ]);

                // Create next scheduled maintenance entry
                \App\Models\MaintenanceLog::create([
                    'equipment_id' => $equipment->id,
                    'user_id' => $adminUser ? $adminUser->id : 1,
                    'maintenance_type' => \App\Models\MaintenanceLog::TYPE_SCHEDULED,
                    'description' => 'Next scheduled maintenance',
                    'status' => \App\Models\MaintenanceLog::STATUS_PENDING,
                    'scheduled_date' => $maintData['next_maintenance']->format('Y-m-d'),
                    'priority' => \App\Models\MaintenanceLog::PRIORITY_MEDIUM,
                    'notes' => 'Scheduled based on ' . $maintData['interval_days'] . ' day maintenance interval',
                ]);
            }
        }

        $this->command->info('✅ Created maintenance logs');

        // Update assignment equipment IDs and create assignments
        $createdEquipment = Equipment::all();
        foreach ($assignments as $index => $assignment) {
            $equipmentIndex = $assignment['equipment_id'] - 1;
            if (isset($createdEquipment[$equipmentIndex])) {
                $equipment = $createdEquipment[$equipmentIndex];
                $equipment->update([
                    'assigned_to_type' => $assignment['assigned_to_type'],
                    'assigned_to_id' => $assignment['assigned_to_id'],
                    'assigned_by_type' => $assignment['assigned_by_type'],
                    'assigned_by_id' => $assignment['assigned_by_id'],
                    'assigned_at' => $assignment['assigned_at'],
                    'status' => 'assigned',
                ]);
            }
        }

        $this->command->info('✅ Created equipment assignments');

        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('   📚 Campuses: ' . Campus::count());
        $this->command->info('   🏢 Offices: ' . Office::count());
        $this->command->info('   👥 Staff Members: ' . User::where('is_staff', true)->count());
        $this->command->info('   🔧 Equipment: ' . Equipment::count());
        $this->command->info('   📋 Equipment Assignments: ' . Equipment::whereNotNull('assigned_to_id')->count());



    }




}
