<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--force : Force backup even if recently created} {--scheduled : Run as scheduled backup (respects settings)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if this is a scheduled run and verify settings
        if ($this->option('scheduled')) {
            $backupSettings = \App\Models\Setting::getBackupSettings();
            
            if (!$backupSettings['enabled']) {
                $this->info('Automatic backups are disabled in settings.');
                return 0;
            }
            
            if (!\App\Models\Setting::isBackupScheduledForDate()) {
                $this->info('Backup not scheduled for today.');
                return 0;
            }
        }

        $this->info('Starting database backup...');

        // Check if backup was recently created (unless --force is used)
        if (!$this->option('force')) {
            $recentBackup = DB::table('activities')
                ->where('type', 'database_backup')
                ->where('created_at', '>=', Carbon::now()->subHours(2))
                ->first();

            if ($recentBackup) {
                $this->info('Recent backup found. Skipping. Use --force to override.');
                return 0;
            }
        }

        try {
            // Create backup directory if it doesn't exist
            $backupDir = storage_path('backups');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            // Generate backup filename
            $filename = 'backup_' . Carbon::now()->format('Y_m_d_His') . '.sql';
            $backupPath = $backupDir . '/' . $filename;

            // Get database connection details
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            // Create backup using mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($backupPath)
            );

            // Execute backup command
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && File::exists($backupPath)) {
                // Log successful backup
                DB::table('activities')->insert([
                    'user_id' => 1,
                    'type' => 'database_backup',
                    'description' => "Database backup created: {$filename}",
                    'created_at' => Carbon::now(),
                ]);

                $this->info("Database backup created successfully: {$filename}");
                
                // Clean up old backups (keep last 7 days)
                $this->cleanupOldBackups($backupDir);

                return 0;
            } else {
                $this->error('Failed to create database backup.');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clean up old backup files (keep last 7 days)
     */
    private function cleanupOldBackups($backupDir)
    {
        $files = File::glob($backupDir . '/backup_*.sql');
        $cutoffDate = Carbon::now()->subDays(7);

        foreach ($files as $file) {
            if (File::exists($file) && Carbon::createFromTimestamp(File::lastModified($file))->lt($cutoffDate)) {
                File::delete($file);
                $this->line("Deleted old backup: " . basename($file));
            }
        }
    }
}
