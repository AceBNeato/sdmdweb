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
                $details[] = 'Automatic backup ' . ($newValues['backup_auto_enabled'] ? 'enabled' : 'disabled');
            }
            
            if ($oldValues['backup_auto_time'] !== $newValues['backup_auto_time']) {
                $details[] = 'Backup time changed from ' . $oldValues['backup_auto_time'] . ' to ' . $newValues['backup_auto_time'];
            }
            
            if ($oldValues['backup_auto_days'] !== $newValues['backup_auto_days']) {
                $details[] = 'Backup days changed from "' . $oldValues['backup_auto_days'] . '" to "' . $newValues['backup_auto_days'] . '"';
            }

            Activity::logSettingsUpdate(
                'Backup Settings',
                $description,
                $oldValues,
                $newValues,
                implode('; ', $details)
            );
        }

        // Handle AJAX requests
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully.'
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }

    // ==========================================
    // SYSTEM MANAGEMENT METHODS
    // ==========================================

    /**
     * Display the system management dashboard.
     */
    public function systemIndex()
    {
        return view('system.index');
    }

    // ==========================================
    // CATEGORY MANAGEMENT
    // ==========================================

    /**
     * Display all categories.
     */
    public function categories(Request $request)
    {
        $query = Category::withCount('equipment');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by status
        // Note: Status filtering removed as is_active column is being dropped

        $categories = $query->orderBy('name')->paginate(15);

        return view('system.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function createCategory()
    {
        return view('system.categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $category = Category::create($request->only(['name']));

        // Log category creation
        Activity::logSystemManagement(
            'Category Created',
            'Created new category: ' . $category->name,
            'categories',
            $category->id,
            ['name' => $category->name],
            null,
            'Category'
        );

        return redirect()->route('admin.settings.system.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing a category.
     */
    public function editCategory(Category $category)
    {
        return view('system.categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function updateCategory(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Store old values for logging
        $oldValues = ['name' => $category->name];
        $newValues = ['name' => $request->name];

        $category->update($request->only(['name']));

        // Log category update
        Activity::logSystemManagement(
            'Category Updated',
            'Updated category from "' . $oldValues['name'] . '" to "' . $newValues['name'] . '"',
            'categories',
            $category->id,
            $newValues,
            $oldValues,
            'Category'
        );

        return redirect()->route('admin.settings.system.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete the specified category.
     */
    public function destroyCategory(Category $category)
    {
        // Check if category has equipment
        if ($category->hasEquipment()) {
            return redirect()->back()
                ->with('error', 'Cannot delete category that contains equipment. Please reassign or remove all equipment first.');
        }

        // Store values for logging
        $categoryData = ['name' => $category->name];
        $equipmentCount = $category->equipment()->count();

        $category->delete();

        // Log category deletion
        Activity::logSystemManagement(
            'Category Deleted',
            'Deleted category: ' . $categoryData['name'] . ' (had ' . $equipmentCount . ' equipment items)',
            'categories',
            $category->id,
            null,
            $categoryData,
            'Category'
        );

        return redirect()->route('admin.settings.system.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    // ==========================================
    // EQUIPMENT TYPE MANAGEMENT
    // ==========================================

    /**
     * Display all equipment types.
     */
    public function equipmentTypes(Request $request)
    {
        $query = EquipmentType::withCount('equipment');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by status
        // Note: Status filtering removed as is_active column is being dropped

        $equipmentTypes = $query->orderBy('name')->paginate(15);

        return view('system.equipment-types.index', compact('equipmentTypes'));
    }

    /**
     * Show the form for creating a new equipment type.
     */
    public function createEquipmentType()
    {
        return view('system.equipment-types.create');
    }

    /**
     * Store a newly created equipment type.
     */
    public function storeEquipmentType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:equipment_types,name',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $equipmentType = EquipmentType::create($request->only(['name']));

        // Log equipment type creation
        Activity::logSystemManagement(
            'Equipment Type Created',
            'Created new equipment type: ' . $equipmentType->name,
            'equipment_types',
            $equipmentType->id,
            ['name' => $equipmentType->name],
            null,
            'Equipment Type'
        );

        return redirect()->route('admin.settings.system.equipment-types.index')
            ->with('success', 'Equipment type created successfully.');
    }

    /**
     * Show the form for editing an equipment type.
     */
    public function editEquipmentType(EquipmentType $equipmentType)
    {
        return view('system.equipment-types.edit', compact('equipmentType'));
    }

    /**
     * Update the specified equipment type.
     */
    public function updateEquipmentType(Request $request, EquipmentType $equipmentType)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:equipment_types,name,' . $equipmentType->id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Store old values for logging
        $oldValues = ['name' => $equipmentType->name];
        $newValues = ['name' => $request->name];

        $equipmentType->update($request->only(['name']));

        // Log equipment type update
        Activity::logSystemManagement(
            'Equipment Type Updated',
            'Updated equipment type from "' . $oldValues['name'] . '" to "' . $newValues['name'] . '"',
            'equipment_types',
            $equipmentType->id,
            $newValues,
            $oldValues,
            'Equipment Type'
        );

        return redirect()->route('admin.settings.system.equipment-types.index')
            ->with('success', 'Equipment type updated successfully.');
    }

    /**
     * Delete the specified equipment type.
     */
    public function destroyEquipmentType(EquipmentType $equipmentType)
    {
        // Check if equipment type has equipment
        if ($equipmentType->equipment()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete equipment type that contains equipment. Please reassign or remove all equipment first.');
        }

        // Store values for logging
        $equipmentTypeData = ['name' => $equipmentType->name];
        $equipmentCount = $equipmentType->equipment()->count();

        $equipmentType->delete();

        // Log equipment type deletion
        Activity::logSystemManagement(
            'Equipment Type Deleted',
            'Deleted equipment type: ' . $equipmentTypeData['name'] . ' (had ' . $equipmentCount . ' equipment items)',
            'equipment_types',
            $equipmentType->id,
            null,
            $equipmentTypeData,
            'Equipment Type'
        );

        return redirect()->route('admin.settings.system.equipment-types.index')
            ->with('success', 'Equipment type deleted successfully.');
    }
}
