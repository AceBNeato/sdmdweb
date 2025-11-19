<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledBackup extends Command
{
    protected $signature = 'backup:run-scheduled';

    protected $description = 'Execute the automatic database backup if the schedule matches the current time.';

    public function __construct(private BackupService $backupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $settings = Setting::getBackupSettings();

        if (!($settings['enabled'] ?? false)) {
            $this->info('Automatic backups are disabled.');
            return self::SUCCESS;
        }

        $scheduledDays = $settings['days'] ?? [];
        $now = Carbon::now();
        $weekday = strtolower($now->format('l'));

        if (empty($scheduledDays) || !in_array($weekday, $scheduledDays, true)) {
            $this->info('No automatic backup scheduled for today.');
            return self::SUCCESS;
        }

        $timeString = $settings['time'] ?? '02:00';
        [$hour, $minute] = array_pad(explode(':', $timeString, 2), 2, '00');
        $scheduledDateTime = (clone $now)->setTime((int) $hour, (int) $minute, 0);

        if ($now->lt($scheduledDateTime)) {
            $this->info('Scheduled backup time has not been reached yet.');
            return self::SUCCESS;
        }

        $lastRun = null;
        if (!empty($settings['last_run_at'])) {
            try {
                $lastRun = Carbon::parse($settings['last_run_at']);
            } catch (\Throwable $th) {
                Log::warning('Failed to parse backup_last_run_at setting: ' . $th->getMessage());
            }
        }

        if ($lastRun && $lastRun->isSameDay($now) && $lastRun->greaterThanOrEqualTo($scheduledDateTime)) {
            $this->info('Backup already executed for the current schedule.');
            return self::SUCCESS;
        }

        try {
            $filename = $this->backupService->createBackup();
            Setting::recordBackupRun();

            $this->info('Scheduled backup created: ' . $filename);
            return self::SUCCESS;
        } catch (\Throwable $throwable) {
            Log::error('Scheduled backup failed: ' . $throwable->getMessage());
            $this->error('Scheduled backup failed: ' . $throwable->getMessage());

            return self::FAILURE;
        }
    }
}
