<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Setting;
use App\Models\Category;
use App\Models\EquipmentType;
use App\Models\Equipment;
use App\Models\Campus;
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
            try {
                Activity::logSettingsUpdate(
                    'Session Settings',
                    'Updated session lockout duration',
                    $oldValues,
                    $newValues,
                    'Session lockout changed from ' . $oldValues['session_lockout_minutes'] . ' to ' . $newValues['session_lockout_minutes'] . ' minutes'
                );
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::warning('Failed to log settings update activity: ' . $e->getMessage());
            }

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

        // Handle AJAX requests for both session and backup settings forms
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true
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

    // Category Management Methods
    public function categories(Request $request)
    {
        $categories = Category::withCount('equipment')
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10);

        return view('settings.categories.index', compact('categories'));
    }

    public function createCategory()
    {
        return view('settings.categories.create');
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.settings.categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function editCategory(Category $category)
    {
        return view('settings.categories.edit', compact('category'));
    }

    public function updateCategory(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.settings.categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroyCategory(Category $category)
    {
        if ($category->equipment()->count() > 0) {
            return redirect()->route('admin.settings.categories.index')
                ->with('error', 'Cannot delete category that has equipment assigned!');
        }

        $category->forceDelete();

        return redirect()->route('admin.settings.categories.index')
            ->with('success', 'Category deleted successfully!');
    }

    public function toggleCategory(Category $category)
    {
        $category->update([
            'is_active' => !$category->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category status updated successfully!'
        ]);
    }

    // Equipment Type Management Methods
    public function equipmentTypes(Request $request)
    {
        $equipmentTypes = EquipmentType::withCount('equipment')
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10);

        return view('settings.equipment-types.index', compact('equipmentTypes'));
    }

    public function createEquipmentType()
    {
        return view('settings.equipment-types.create');
    }

    public function storeEquipmentType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:equipment_types,name',
        ]);

        EquipmentType::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.settings.equipment-types.index')
            ->with('success', 'Equipment type created successfully!');
    }

    public function editEquipmentType(EquipmentType $equipmentType)
    {
        return view('settings.equipment-types.edit', compact('equipmentType'));
    }

    public function updateEquipmentType(Request $request, EquipmentType $equipmentType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:equipment_types,name,' . $equipmentType->id,
        ]);

        $equipmentType->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.settings.equipment-types.index')
            ->with('success', 'Equipment type updated successfully!');
    }

    public function destroyEquipmentType(EquipmentType $equipmentType)
    {
        if ($equipmentType->equipment()->count() > 0) {
            return redirect()->route('admin.settings.equipment-types.index')
                ->with('error', 'Cannot delete equipment type that has equipment assigned!');
        }

        $equipmentType->delete();

        return redirect()->route('admin.settings.equipment-types.index')
            ->with('success', 'Equipment type deleted successfully!');
    }

    public function toggleEquipmentType(EquipmentType $equipmentType)
    {
        $equipmentType->update([
            'is_active' => !$equipmentType->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Equipment type status updated successfully!'
        ]);
    }
}