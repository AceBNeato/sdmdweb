<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Check for backup triggers every minute (more frequent for better reliability)
        $schedule->command('backup:check-triggers')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/backup-scheduler.log'));

        // Direct backup using your existing settings as primary method
        $schedule->command('backup:database --scheduled')
            ->daily()
            ->at(function () {
                // Get backup time from settings
                $backupTime = \App\Models\Setting::getValue('backup_auto_time', '02:00');
                return $backupTime ?: '02:00';
            })
            ->when(function () {
                // Only run if backup is enabled and scheduled for today
                return \App\Models\Setting::isBackupScheduledForDate();
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/daily-backup.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
