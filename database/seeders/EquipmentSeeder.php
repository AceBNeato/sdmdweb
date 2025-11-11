<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Equipment;
use App\Models\Office;
use App\Models\EquipmentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        // Get all offices (now only 3: SDMD, IT, COA)
        $offices = Office::all();
        if ($offices->count() < 3) {
            $this->command->error('Not enough offices found');
            return;
        }

        // Equipment types data (map to slugs in equipment_types table)
        $equipmentTypes = [
            'laptop' => [
                'brands' => ['Dell', 'HP', 'Lenovo', 'Apple'],
                'models' => ['Latitude', 'EliteBook', 'ThinkPad', 'MacBook Pro'],
                'specs' => 'Intel Core i7, 16GB RAM, 512GB SSD, 14" FHD display',
            ],
            'desktop' => [
                'brands' => ['Dell', 'HP', 'Lenovo'],
                'models' => ['OptiPlex', 'EliteDesk', 'ThinkCentre'],
                'specs' => 'Intel Core i5, 16GB RAM, 512GB SSD, Windows 11 Pro',
            ],
            'printer' => [
                'brands' => ['Canon', 'HP', 'Brother'],
                'models' => ['imageCLASS', 'LaserJet', 'HL-L'],
                'specs' => 'Multifunction laser printer, 40ppm, Duplex, Network ready',
            ],
            'projector' => [
                'brands' => ['Epson', 'BenQ', 'Optoma'],
                'models' => ['PowerLite', 'MH', 'HD'],
                'specs' => 'WXGA projector, 5200 lumens, 1280x800 resolution',
            ],
            'network' => [
                'brands' => ['Cisco', 'TP-Link', 'Netgear'],
                'models' => ['Catalyst', 'Archer', 'GS'],
                'specs' => '24-port managed switch, Gigabit Ethernet',
            ],
            'monitor' => [
                'brands' => ['Dell', 'HP', 'LG'],
                'models' => ['UltraSharp', 'EliteDisplay', 'UltraFine'],
                'specs' => '27" 4K UHD monitor, IPS panel, 3840x2160 resolution',
            ],
            'scanner' => [
                'brands' => ['Canon', 'Epson', 'Brother'],
                'models' => ['imageFORMULA', 'Perfection', 'ADS'],
                'specs' => 'Document scanner, 60ppm duplex, 100-sheet ADF',
            ],
            'server' => [
                'brands' => ['Dell', 'HP', 'Supermicro'],
                'models' => ['PowerEdge', 'ProLiant', 'SuperServer'],
                'specs' => 'Rack server, Intel Xeon, 128GB RAM, 4TB storage',
            ],
        ];

        $departments = [
            'SDMD' => 'Systems Development and Maintenance Department',
            'IT' => 'Information Technology',
            'COA' => 'College of Agriculture',
        ];

        $locations = [
            'Office Workstation',
            'Conference Room',
            'Computer Lab',
            'Library Area',
            'Server Room',
            'Print Station',
        ];

        $statuses = ['serviceable', 'for_repair', 'defective'];
        $conditions = ['excellent', 'good', 'fair'];

        $equipmentCount = 0;
        $officesArray = $offices; // Use all offices

        foreach ($officesArray as $officeIndex => $office) {
            $department = $departments[$office->code] ?? 'General';

            for ($i = 0; $i < 10; $i++) { // 10 equipment per office for total of 30
                $type = array_rand($equipmentTypes);
                $typeData = $equipmentTypes[$type];
                $brand = $typeData['brands'][array_rand($typeData['brands'])];
                $model = $typeData['models'][array_rand($typeData['models'])];
                $spec = $typeData['specs'];

                // Get equipment type ID
                $equipmentType = EquipmentType::where('slug', $type)->first();
                if (!$equipmentType) {
                    $this->command->error("Equipment type '{$type}' not found");
                    continue;
                }

                $modelNumber = $brand . '-' . strtoupper(substr($type, 0, 3)) . '-' . str_pad($equipmentCount + 1, 3, '0', STR_PAD_LEFT);
                $serialNumber = strtoupper($brand) . rand(100000000, 999999999);
                $status = $statuses[array_rand($statuses)];
                $condition = $conditions[array_rand($conditions)];

                $equipment = Equipment::create([
                    'model_number' => $modelNumber,
                    'serial_number' => $serialNumber,
                    'equipment_type_id' => $equipmentType->id,
                    'description' => $brand . ' ' . $model . ' - ' . $spec,
                    'purchase_date' => now()->subDays(rand(30, 730))->format('Y-m-d'),
                    'office_id' => $office->id,
                    'qr_code' => 'EQP-' . strtoupper(Str::random(8)),
                    'qr_code_image_path' => 'qrcodes/equipment_' . ($equipmentCount + 1) . '.png',
                    'status' => $status,
                    'condition' => $condition,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Generate QR code
                $qrData = json_encode([
                    'id' => $equipment->id,
                    'type' => 'equipment',
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'equipment_type' => $equipment->equipment_type,
                    'office' => $equipment->office->name ?? 'N/A',
                    'status' => $equipment->status,
                ]);

                try {
                    $qrCodeImage = QrCode::format('png')
                        ->size(200)
                        ->generate($qrData);

                    $fileName = 'equipment_' . $equipment->id . '.png';
                    $path = 'qrcodes/' . $fileName;
                    Storage::disk('public')->put($path, $qrCodeImage);

                    $equipment->update(['qr_code_image_path' => $path]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to generate QR code for equipment ID: ' . $equipment->id . ' - ' . $e->getMessage());
                }

                $equipmentCount++;
            }
        }

        $this->command->info('✅ Seeded ' . $equipmentCount . ' equipment items across ' . $officesArray->count() . ' offices');
    }
}
