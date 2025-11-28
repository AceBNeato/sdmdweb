<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreateAutomaticBackup extends Command
{
    protected $signature = 'backup:auto';
    protected $description = 'Create automatic database backup based on settings';

    public function __construct(protected BackupService $backupService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $settings = Setting::getBackupSettings();

        // If auto backup is disabled
        if (!$settings['enabled']) {
            $this->info('Automatic backups are disabled.');
            return 0;
        }

        $configuredTime = Carbon::createFromFormat('H:i', $settings['time']);
        $now = Carbon::now();
        $today = strtolower($now->englishDayOfWeek); // monday, tuesday, etc.

        // Check if today is in allowed days
        if (!in_array($today, $settings['days'])) {
            $this->info("Today ({$today}) is not scheduled for backup.");
            return 0;
        }

        // Check if current time matches (within 2-minute window to account for cron precision)
        $currentTime = $now->format('H:i');
        $startWindow = $configuredTime->copy()->subMinutes(1)->format('H:i');
        $endWindow = $configuredTime->copy()->addMinutes(2)->format('H:i');

        if ($currentTime < $startWindow || $currentTime > $endWindow) {
            $this->info("Current time {$currentTime} does not match backup time {$settings['time']}");
            return 0;
        }

        // All conditions met → create backup
        try {
            $filename = $this->backupService->createBackup();
            Setting::recordBackupRun(); // optional: track last run

            $path = $this->backupService->getBackupAbsolutePath($filename);
            $size = file_exists($path) ? filesize($path) : 0;
            $sizeHuman = $this->formatBytes($size);

            $this->info("Automatic backup created: {$filename} ({$sizeHuman})");

            // Log automatic backup in activities for user visibility
            \App\Models\Activity::create([
                'user_id' => 1, // System user
                'type' => 'automatic_backup_created',
                'description' => "Scheduled backup created: {$filename} ({$sizeHuman})",
                'created_at' => now(),
            ]);

            return 0;

        } catch (\Exception $e) {
            Log::error('Automatic backup failed: ' . $e->getMessage());

            // Log automatic backup failure in activities for user visibility
            \App\Models\Activity::create([
                'user_id' => 1, // System user
                'type' => 'automatic_backup_failed',
                'description' => 'Scheduled backup failed: ' . $e->getMessage(),
                'created_at' => now(),
            ]);

            $this->error('Automatic backup failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
