<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearQrCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the QR code cache directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cachePath = storage_path('app/public/qrcodes/cache');
        
        if (!File::exists($cachePath)) {
            $this->info('QR cache directory does not exist: ' . $cachePath);
            return 0;
        }

        try {
            // Get all files in the cache directory
            $files = File::allFiles($cachePath);
            $fileCount = count($files);
            
            if ($fileCount === 0) {
                $this->info('QR cache directory is already empty.');
                return 0;
            }

            // Delete all files
            File::delete($files);
            
            $this->info("Successfully cleared {$fileCount} files from QR cache directory.");
            $this->line('Cache path: ' . $cachePath);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to clear QR cache: ' . $e->getMessage());
            return 1;
        }
    }
}
