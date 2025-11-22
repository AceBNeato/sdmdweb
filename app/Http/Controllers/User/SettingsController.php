<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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

        if ($section === 'session') {
            $request->validate([
                'session_lockout_minutes' => 'required|integer|min:1|max:60',
            ]);

            Setting::setValue(
                'session_lockout_minutes',
                $request->session_lockout_minutes,
                'integer',
                'Session lockout in minutes for screen lock'
            );

        } elseif ($section === 'backup') {
            $request->validate([
                'backup_auto_time' => 'nullable|date_format:H:i',
                'backup_auto_days' => 'nullable|array',
                'backup_auto_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            ]);

            $enabled = $request->boolean('backup_auto_enabled');
            $time = $request->input('backup_auto_time', '02:00');
            $days = array_map('strtolower', (array) $request->input('backup_auto_days', []));

            Setting::setBackupSettings($enabled, $time, $days);
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

        Category::create($request->only(['name']));

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

        $category->update($request->only(['name']));

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

        $category->delete();

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

        EquipmentType::create($request->only(['name']));

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

        $equipmentType->update($request->only(['name']));

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

        $equipmentType->delete();

        return redirect()->route('admin.settings.system.equipment-types.index')
            ->with('success', 'Equipment type deleted successfully.');
    }
}
