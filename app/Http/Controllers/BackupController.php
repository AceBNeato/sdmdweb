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
        // Only super-admin can access
        if (!auth()->user()->is_super_admin) {
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
        if (!auth()->user()->is_super_admin) {
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
        if (!auth()->user()->is_super_admin) {
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
        if (!auth()->user()->is_super_admin) {
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
        if (!auth()->user()->is_super_admin) {
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
        if (!auth()->user()->is_super_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $backups = $this->backupService->listBackups();

        return response()->json([
            'backups' => $backups,
            'count' => count($backups),
        ]);
    }
}
