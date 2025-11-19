<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Http\Request;

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
        $request->validate([
            'session_lockout_minutes' => 'required|integer|min:1|max:60',
            'backup_auto_time' => 'nullable|date_format:H:i',
            'backup_auto_days' => 'nullable|array',
            'backup_auto_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        Setting::setValue('session_lockout_minutes', $request->session_lockout_minutes, 'integer', 'Session lockout in minutes for screen lock');

        $enabled = $request->boolean('backup_auto_enabled');
        $time = $request->input('backup_auto_time', '02:00');
        $days = array_map('strtolower', (array) $request->input('backup_auto_days', []));

        Setting::setBackupSettings($enabled, $time, $days);

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
