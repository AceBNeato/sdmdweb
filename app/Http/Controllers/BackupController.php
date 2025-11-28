<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackupController extends Controller
{
    public function __construct(private BackupService $backupService)
    {
    }

    /**
     * Display the backup management page.
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermissionTo('settings.manage')) {
            abort(403, 'Unauthorized');
        }
        
        // Get list of existing backups
        $backups = $this->backupService->listBackups();

        return view('backup.index', compact('backups'));
    }

    /**
     * Create a new database backup.
     */
    public function backup(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermissionTo('settings.manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filename = $this->backupService->createBackup();
            Setting::recordBackupRun();

            // Get backup file size for logging
            $backupPath = $this->backupService->getBackupAbsolutePath($filename);
            $fileSize = file_exists($backupPath) ? filesize($backupPath) : 0;
            $fileSizeHuman = $fileSize > 0 ? $this->formatBytes($fileSize) : 'Unknown';

            // Log successful backup creation
            Activity::logSystemManagement(
                'Backup Created',
                'Created database backup: ' . $filename . ' (' . $fileSizeHuman . ')',
                'backups',
                $filename,
                [
                    'filename' => $filename,
                    'size_bytes' => $fileSize,
                    'size_human' => $fileSizeHuman,
                    'created_by' => 'manual'
                ],
                null,
                'Backup'
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename,
                'size_human' => $fileSizeHuman
            ]);

        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());

            // Log failed backup attempt
            Activity::logSystemManagement(
                'Backup Failed',
                'Failed to create database backup: ' . $e->getMessage(),
                'backups',
                null,
                null,
                ['error' => $e->getMessage()],
                'Backup'
            );

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Download a backup file.
     */
    public function download($filename)
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermissionTo('settings.manage')) {
            abort(403, 'Unauthorized');
        }

        $filename = basename($filename);

        try {
            $path = $this->backupService->getBackupAbsolutePath($filename);
            $fileSize = file_exists($path) ? filesize($path) : 0;
            $fileSizeHuman = $fileSize > 0 ? $this->formatBytes($fileSize) : 'Unknown';

            // Log backup download
            Activity::logSystemManagement(
                'Backup Downloaded',
                'Downloaded backup file: ' . $filename . ' (' . $fileSizeHuman . ')',
                'backups',
                $filename,
                [
                    'filename' => $filename,
                    'size_bytes' => $fileSize,
                    'size_human' => $fileSizeHuman,
                    'downloaded_by' => auth()->user()->name
                ],
                null,
                'Backup'
            );

        } catch (\RuntimeException $exception) {
            // Log failed download attempt
            Activity::logSystemManagement(
                'Backup Download Failed',
                'Failed to download backup file: ' . $filename . ' - ' . $exception->getMessage(),
                'backups',
                $filename,
                null,
                ['error' => $exception->getMessage(), 'filename' => $filename],
                'Backup'
            );
            
            abort(404, $exception->getMessage());
        }

        return response()->download($path);
    }

    /**
     * Restore database from backup file.
     */
    public function restore(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermissionTo('settings.manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $filename = $request->input('filename');

        if ($filename) {
            $request->validate([
                'filename' => 'required|string'
            ]);

            try {
                // Get backup file info before restore
                $backupPath = $this->backupService->getBackupAbsolutePath($filename);
                $fileSize = file_exists($backupPath) ? filesize($backupPath) : 0;
                $fileSizeHuman = $fileSize > 0 ? $this->formatBytes($fileSize) : 'Unknown';

                $this->backupService->restoreFromExisting($filename);

                // Log successful restore
                Activity::logSystemManagement(
                    'Database Restored',
                    'Restored database from backup: ' . $filename . ' (' . $fileSizeHuman . ')',
                    'backups',
                    $filename,
                    [
                        'filename' => $filename,
                        'size_bytes' => $fileSize,
                        'size_human' => $fileSizeHuman,
                        'restored_by' => auth()->user()->name,
                        'restore_type' => 'existing'
                    ],
                    null,
                    'Backup'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Database restored successfully.'
                ]);

            } catch (\Exception $e) {
                Log::error('Restore failed: ' . $e->getMessage());

                // Log failed restore attempt
                Activity::logSystemManagement(
                    'Database Restore Failed',
                    'Failed to restore database from backup: ' . $filename . ' - ' . $e->getMessage(),
                    'backups',
                    $filename,
                    null,
                    ['error' => $e->getMessage(), 'filename' => $filename, 'restore_type' => 'existing'],
                    'Backup'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Restore failed: ' . $e->getMessage()
                ], 500);
            }
        }

        $request->validate([
            'backup_file' => 'required|file|mimes:sql|max:51200' // 50MB max
        ]);

        try {
            $file = $request->file('backup_file');
            $fileSize = $file->getSize();
            $fileSizeHuman = $this->formatBytes($fileSize);
            $originalName = $file->getClientOriginalName();

            $this->backupService->restoreFromUploadedFile($file->getRealPath());

            // Log successful restore from uploaded file
            Activity::logSystemManagement(
                'Database Restored',
                'Restored database from uploaded file: ' . $originalName . ' (' . $fileSizeHuman . ')',
                'backups',
                $originalName,
                [
                    'filename' => $originalName,
                    'size_bytes' => $fileSize,
                    'size_human' => $fileSizeHuman,
                    'restored_by' => auth()->user()->name,
                    'restore_type' => 'uploaded'
                ],
                null,
                'Backup'
            );

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage());

            // Log failed restore attempt from uploaded file
            Activity::logSystemManagement(
                'Database Restore Failed',
                'Failed to restore database from uploaded file: ' . ($file->getClientOriginalName() ?? 'Unknown') . ' - ' . $e->getMessage(),
                'backups',
                $file->getClientOriginalName() ?? 'Unknown',
                null,
                ['error' => $e->getMessage(), 'filename' => $file->getClientOriginalName() ?? 'Unknown', 'restore_type' => 'uploaded'],
                'Backup'
            );

            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a backup file.
     */
    public function delete($filename)
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermissionTo('settings.manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filename = basename($filename);
            
            // Get backup file info before deletion
            $backupPath = $this->backupService->getBackupAbsolutePath($filename);
            $fileSize = file_exists($backupPath) ? filesize($backupPath) : 0;
            $fileSizeHuman = $fileSize > 0 ? $this->formatBytes($fileSize) : 'Unknown';
            
            $this->backupService->deleteBackup($filename);

            // Log successful backup deletion
            Activity::logSystemManagement(
                'Backup Deleted',
                'Deleted backup file: ' . $filename . ' (' . $fileSizeHuman . ')',
                'backups',
                $filename,
                null,
                [
                    'filename' => $filename,
                    'size_bytes' => $fileSize,
                    'size_human' => $fileSizeHuman,
                    'deleted_by' => auth()->user()->name
                ],
                'Backup'
            );

            return response()->json(['success' => true, 'message' => 'Backup deleted successfully']);
            
        } catch (\Throwable $e) {
            Log::error('Failed to delete backup: ' . $e->getMessage());

            // Log failed deletion attempt
            Activity::logSystemManagement(
                'Backup Deletion Failed',
                'Failed to delete backup file: ' . $filename . ' - ' . $e->getMessage(),
                'backups',
                $filename,
                null,
                ['error' => $e->getMessage(), 'filename' => $filename],
                'Backup'
            );

            return response()->json(['error' => 'Failed to delete backup'], 500);
        }
    }

    /**
     * Return JSON list of backups for AJAX consumers.
     */
    public function list(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermissionTo('settings.manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $backups = $this->backupService->listBackups();

        return response()->json([
            'backups' => $backups,
            'count' => count($backups),
        ]);
    }

    /**
     * Handle automatic backup triggered via AJAX.
     */
    public function autoBackup(Request $request)
    {
        // For automatic backups, we don't require user authentication
        // but we validate the request to prevent abuse
        try {
            $settings = Setting::getBackupSettings();

            // If auto backup is disabled, return early
            if (!$settings['enabled']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Automatic backups are disabled'
                ]);
            }

            $configuredTime = \Carbon\Carbon::createFromFormat('H:i', $settings['time']);
            $now = \Carbon\Carbon::now();
            $today = strtolower($now->englishDayOfWeek);

            // Check if today is in allowed days
            if (!in_array($today, $settings['days'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Today ({$today}) is not scheduled for backup"
                ]);
            }

            // Check if backup was already run recently (within last 1 minute)
            $lastRunAt = $settings['last_run_at'] ?? null;
            if ($lastRunAt) {
                $lastRun = \Carbon\Carbon::parse($lastRunAt);
                
                // Calculate actual time difference in seconds, then convert to minutes
                $actualTimeDiff = $now->timestamp - $lastRun->timestamp;
                $minutesSinceLastRun = max(0, floor($actualTimeDiff / 60));
                
                // Calculate next backup time using the same logic as the frontend
                $backupTime = $settings['time']; // e.g., "18:55"
                $backupDays = $settings['days']; // e.g., ["friday", "saturday"]
                $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                
                [$hour, $minute] = array_map('intval', explode(':', $backupTime));
                $nextDate = $now->copy();
                
                // Find the next scheduled day (same logic as frontend)
                for ($i = 0; $i < 7; $i++) {
                    $currentDayName = strtolower($nextDate->englishDayOfWeek);
                    
                    if (in_array($currentDayName, $backupDays)) {
                        $backupDateTime = $nextDate->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
                        
                        if ($backupDateTime->gt($now)) {
                            $nextBackupTime = $backupDateTime->format('M d, Y h:i A');
                            break;
                        }
                    }
                    
                    $nextDate->addDay();
                }
                
                // Fallback if no calculation worked
                if (!isset($nextBackupTime)) {
                    $nextBackupTime = $now->copy()->addDay()->setHour($hour)->setMinute($minute)->setSecond(0)->format('M d, Y h:i A');
                }
                
                // Debug logging to see the actual values
                \Log::info("Backup cooldown check: Last run at '{$lastRunAt}', Now: '{$now->toDateTimeString()}', Last run timestamp: {$lastRun->timestamp}, Now timestamp: {$now->timestamp}, Actual seconds diff: {$actualTimeDiff}, Minutes diff: {$minutesSinceLastRun}");
                
                if ($minutesSinceLastRun < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => "Backup already run {$minutesSinceLastRun} minutes ago. Skipping to prevent duplicates.",
                        'skipped' => true,
                        'minutes_ago' => $minutesSinceLastRun,
                        'next_backup_time' => $nextBackupTime
                    ]);     
                }
            }

            // Check if current time matches (within 30-second window: 15 seconds before to 15 seconds after)
            $currentTime = $now;
            $startWindow = $configuredTime->copy()->subSeconds(15);
            $endWindow = $configuredTime->copy()->addSeconds(15);

            if ($currentTime->lt($startWindow) || $currentTime->gt($endWindow)) {
                return response()->json([
                    'success' => false,
                    'message' => "Current time {$now->format('H:i')} does not match backup time {$settings['time']} (window: {$startWindow->format('H:i')} - {$endWindow->format('H:i')})"
                ]);
            }

            // All conditions met → create backup
            $filename = $this->backupService->createBackup();
            Setting::recordBackupRun();

            $path = $this->backupService->getBackupAbsolutePath($filename);
            $size = file_exists($path) ? filesize($path) : 0;
            $sizeHuman = $this->formatBytes($size);

            // Log automatic backup in activities for user visibility
            Activity::create([
                'user_id' => 1, // System user
                'type' => 'automatic_backup_created',
                'description' => "Scheduled backup created: {$filename} ({$sizeHuman})",
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Automatic backup created successfully',
                'filename' => $filename,
                'size_human' => $sizeHuman
            ]);

        } catch (\Exception $e) {
            Log::error('Automatic backup failed: ' . $e->getMessage());

            // Log automatic backup failure in activities for user visibility
            Activity::create([
                'user_id' => 1, // System user
                'type' => 'automatic_backup_failed',
                'description' => 'Scheduled backup failed: ' . $e->getMessage(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Automatic backup failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
