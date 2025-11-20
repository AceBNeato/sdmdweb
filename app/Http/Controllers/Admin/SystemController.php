<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\EquipmentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SystemController extends Controller
{
    /**
     * Display the system management dashboard.
     */
    public function index()
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
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

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
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Category::create($request->all());

        return redirect()->route('admin.system.categories.index')
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
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $category->update($request->all());

        return redirect()->route('admin.system.categories.index')
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

        return redirect()->route('admin.system.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Toggle category active status.
     */
    public function toggleCategory(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        $status = $category->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Category {$status} successfully.");
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
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $equipmentTypes = $query->orderBy('sort_order')->orderBy('name')->paginate(15);

        return view('system.equipment-types.index', compact('equipmentTypes'));
    }

    /**
     * Show the form for creating a new equipment type.
     */
    public function createEquipmentType()
    {
        $maxSortOrder = EquipmentType::max('sort_order') ?? 0;
        return view('system.equipment-types.create', compact('maxSortOrder'));
    }

    /**
     * Store a newly created equipment type.
     */
    public function storeEquipmentType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:equipment_types,name',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        EquipmentType::create($data);

        return redirect()->route('admin.system.equipment-types.index')
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
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        $equipmentType->update($data);

        return redirect()->route('admin.system.equipment-types.index')
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

        return redirect()->route('admin.system.equipment-types.index')
            ->with('success', 'Equipment type deleted successfully.');
    }

    /**
     * Toggle equipment type active status.
     */
    public function toggleEquipmentType(EquipmentType $equipmentType)
    {
        $equipmentType->update(['is_active' => !$equipmentType->is_active]);

        $status = $equipmentType->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Equipment type {$status} successfully.");
    }

    /**
     * Update equipment types sort order.
     */
    public function updateSortOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'equipment_types' => 'required|array',
            'equipment_types.*.id' => 'required|integer|exists:equipment_types,id',
            'equipment_types.*.sort_order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid data provided.'], 422);
        }

        foreach ($request->equipment_types as $item) {
            EquipmentType::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true, 'message' => 'Sort order updated successfully.']);
    }
}
