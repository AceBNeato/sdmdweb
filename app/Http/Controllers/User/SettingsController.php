<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Setting;
use App\Models\Category;
use App\Models\EquipmentType;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index()
    {
        $settings = [
            'session_timeout_minutes' => Setting::getSessionTimeoutMinutes(),
            'session_lockout_minutes' => Setting::getValue('session_lockout_minutes', Setting::getSessionTimeoutMinutes()),
        ];

        $backupSettings = Setting::getBackupSettings();
        $backups = $this->backupService->listBackups();

        return view('settings.index', compact('settings', 'backupSettings', 'backups'));
    }

    public function update(Request $request)
    {
        $section = $request->input('section', 'session');
        $oldValues = [];
        $newValues = [];

        if ($section === 'session') {
            $request->validate([
                'session_lockout_minutes' => 'required|integer|min:1|max:60',
            ]);

            // Get old value for logging
            $oldValues['session_lockout_minutes'] = Setting::getValue('session_lockout_minutes', Setting::getSessionTimeoutMinutes());
            $newValues['session_lockout_minutes'] = $request->session_lockout_minutes;

            Setting::setValue(
                'session_lockout_minutes',
                $request->session_lockout_minutes,
                'integer',
                'Session lockout in minutes for screen lock'
            );

            // Log session settings update
            Activity::logSettingsUpdate(
                'Session Settings',
                'Updated session lockout duration',
                $oldValues,
                $newValues,
                'Session lockout changed from ' . $oldValues['session_lockout_minutes'] . ' to ' . $newValues['session_lockout_minutes'] . ' minutes'
            );

        } elseif ($section === 'backup') {
            $request->validate([
                'backup_auto_time' => 'nullable|date_format:H:i',
                'backup_auto_days' => 'nullable|array',
                'backup_auto_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            ]);

            // Get old backup settings for logging
            $oldBackupSettings = Setting::getBackupSettings();
            
            $enabled = $request->boolean('backup_auto_enabled');
            $time = $request->input('backup_auto_time', '02:00');
            $days = array_map('strtolower', (array) $request->input('backup_auto_days', []));

            $oldValues = [
                'backup_auto_enabled' => $oldBackupSettings['enabled'] ?? false,
                'backup_auto_time' => $oldBackupSettings['time'] ?? '02:00',
                'backup_auto_days' => implode(', ', $oldBackupSettings['days'] ?? [])
            ];

            $newValues = [
                'backup_auto_enabled' => $enabled,
                'backup_auto_time' => $time,
                'backup_auto_days' => implode(', ', $days)
            ];

            Setting::setBackupSettings($enabled, $time, $days);

            // Log backup settings update
            $description = 'Updated automatic backup settings';
            $details = [];
            
            if ($oldValues['backup_auto_enabled'] !== $newValues['backup_auto_enabled']) {
                $details[] = sprintf('Auto backup %s', $newValues['backup_auto_enabled'] ? 'enabled' : 'disabled');
            }
            if ($oldValues['backup_auto_time'] !== $newValues['backup_auto_time']) {
                $details[] = sprintf('Time changed from %s to %s', $oldValues['backup_auto_time'], $newValues['backup_auto_time']);
            }
            if ($oldValues['backup_auto_days'] !== $newValues['backup_auto_days']) {
                $details[] = sprintf('Days changed from [%s] to [%s]', $oldValues['backup_auto_days'], $newValues['backup_auto_days']);
            }

            if (!empty($details)) {
                $description .= ': ' . implode(', ', $details);
            }

            Activity::logSystemManagement(
                'Backup Settings Updated',
                $description,
                'settings',
                null,
                $newValues,
                $oldValues,
                'Backup'
            );
        }

        // Handle AJAX requests for backup settings form
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Backup settings updated successfully!'
            ]);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully!');
    }

    /**
     * Get backup settings for AJAX requests
     */
    public function getBackupSettings(Request $request)
    {
        try {
            $settings = Setting::getBackupSettings();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load backup settings'], 500);
        }
    }
}