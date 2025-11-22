<?php

namespace App\Http\Controllers;

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

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
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
        } catch (\RuntimeException $exception) {
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
                $this->backupService->restoreFromExisting($filename);

                return response()->json([
                    'success' => true,
                    'message' => 'Database restored successfully.'
                ]);

            } catch (\Exception $e) {
                Log::error('Restore failed: ' . $e->getMessage());

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
            $this->backupService->restoreFromUploadedFile($file->getRealPath());

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage());

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
            $this->backupService->deleteBackup($filename);

            return response()->json(['success' => true, 'message' => 'Backup deleted successfully']);
        } catch (\Throwable $e) {
            Log::error('Failed to delete backup: ' . $e->getMessage());

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
