<?php

namespace App\Console\Commands;

use App\Models\Equipment;
use App\Services\QrCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:generate {--force : Force regenerate all QR codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate QR codes for equipment that don\'t have them';

    protected $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        parent::__construct();
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $query = Equipment::with(['office', 'equipmentType']);
        
        if (!$force) {
            $query->whereNull('qr_code_image_path');
        }
        
        $equipments = $query->get();
        
        if ($equipments->isEmpty()) {
            $this->info('No equipment found that needs QR codes generated.');
            return;
        }
        
        $this->info("Generating QR codes for {$equipments->count()} equipment(s)...");
        
        $progressBar = $this->output->createProgressBar($equipments->count());
        $progressBar->start();
        
        $generated = 0;
        $failed = 0;
        
        foreach ($equipments as $equipment) {
            try {
                // Generate QR code data
                $qrData = [
                    'type' => 'equipment_url',
                    'url' => route('public.qr-scanner') . '?id=' . $equipment->id,
                    'equipment_id' => $equipment->id,
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                    'office' => $equipment->office ? $equipment->office->name : 'N/A',
                    'status' => $equipment->status,
                ];
                
                // Generate QR code
                $qrPath = $this->qrCodeService->generateQrCode($qrData, '200x200', 'svg');
                
                if ($qrPath) {
                    $equipment->update(['qr_code_image_path' => $qrPath]);
                    $generated++;
                    
                    if ($generated % 10 === 0) {
                        Log::info("Generated QR codes for {$generated} equipment so far");
                    }
                } else {
                    $failed++;
                    Log::warning("Failed to generate QR code for equipment ID: {$equipment->id}");
                }
                
            } catch (\Exception $e) {
                $failed++;
                Log::error("Error generating QR code for equipment ID {$equipment->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("QR code generation completed!");
        $this->info("Generated: {$generated}");
        $this->info("Failed: {$failed}");
        
        if ($failed > 0) {
            $this->warn("Check the logs for details on failed generations.");
        }
    }
}
