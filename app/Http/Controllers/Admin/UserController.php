<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\StoredProcedureService;
use App\Services\EmailService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserController extends Controller
{
    protected $storedProcedureService;
    protected $emailService;

    public function __construct(StoredProcedureService $storedProcedureService, EmailService $emailService)
    {
        $this->storedProcedureService = $storedProcedureService;
        $this->emailService = $emailService;
        // Middleware is now applied in routes/web.php
    }

    /**
     * Display a listing of users with their roles.
     */
    public function index(Request $request)
    {
        // Get search and filter parameters
        $search = $request->get('search');
        $campusFilter = $request->get('campus_id');
        $officeFilter = $request->get('office_id');
        $roleFilter = $request->get('role_id');
        $statusFilter = $request->get('status');

        $usersQuery = User::with(['roles', 'campus', 'office'])
            ->whereDoesntHave('roles', function($q) {
                $q->where('name', 'super-admin');
            });

        // Apply search filter
        if ($search) {
            $usersQuery->where(function($query) use ($search) {
                $query->where('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('position', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        // Apply campus filter
        if ($campusFilter) {
            $usersQuery->where('campus_id', $campusFilter);
        }

        // Apply office filter
        if ($officeFilter) {
            $usersQuery->where('office_id', $officeFilter);
        }

        // Apply role filter
        if ($roleFilter) {
            $usersQuery->whereHas('roles', function($query) use ($roleFilter) {
                $query->where('roles.id', $roleFilter);
            });
        }

        // Apply status filter
        if ($statusFilter !== null) {
            if ($statusFilter === 'active') {
                $usersQuery->where('is_active', true);
            } elseif ($statusFilter === 'inactive') {
                $usersQuery->where('is_active', false);
            }
        }

        $users = $usersQuery->orderBy('first_name')->paginate(10);

        // Get filter options
        $campuses = \App\Models\Campus::where('is_active', true)->orderBy('name')->get();
        $offices = \App\Models\Office::where('is_active', true)->orderBy('name')->get();
        $roles = Role::where('name', '!=', 'super-admin')
            ->orderBy('name')
            ->get();

        return view('accounts.index', compact('users', 'campuses', 'offices', 'roles', 'search', 'campusFilter', 'officeFilter', 'roleFilter', 'statusFilter'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::where('name', '!=', 'super-admin')
            ->orderBy('name')->get();

        $offices = \App\Models\Office::where('is_active', true)->orderBy('name')->get();
        $campuses = \App\Models\Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        return view('accounts.form', compact('roles', 'offices', 'campuses'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'position' => 'required|string|max:255',
            'phone' => 'nullable|regex:/^[0-9]+$/|max:15',
            'office_id' => 'required|exists:offices,id',
            'roles' => 'required|exists:roles,id',
        ]);

        // Prevent non-admins from assigning admin role
        if (!auth()->user()->hasRole('super-admin')) {
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole && $validated['roles'] == $adminRole->id) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign the admin role.')
                    ->withInput();
            }
        }

        // Get the office and derive campus_id
        $office = \App\Models\Office::find($validated['office_id']);
        $campus_id = $office ? $office->campus_id : null;

        // Prepare data for stored procedure
        $userData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $request->phone ?? null,
            'position' => $validated['position'],
            'office_id' => $validated['office_id'],
            'campus_id' => $campus_id,
            'role_ids' => [$validated['roles']], // Single role as array
            'created_by_id' => auth()->id()
        ];

        // Use stored procedure to create user with roles
        $userId = $this->storedProcedureService->createUserWithRoles($userData);

        if (!$userId) {
            return redirect()->back()
                ->with('error', 'Failed to create user. Please try again.')
                ->withInput();
        }

        \Illuminate\Support\Facades\Log::info('User created with ID: ' . $userId . ', requested role ID: ' . $validated['roles']);

        // Get the created user
        $user = User::find($userId);

        if (!$user) {
            \Illuminate\Support\Facades\Log::error('User not found after creation, ID: ' . $userId);
            return redirect()->back()
                ->with('error', 'User was created but could not be retrieved. Please contact administrator.')
                ->withInput();
        }

        \Illuminate\Support\Facades\Log::info('User created successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'assigned_roles' => $user->roles->pluck('name')->toArray()
        ]);

        // Ensure technician role is properly assigned if that's what was requested
        $requestedRole = Role::find($validated['roles']);
        if ($requestedRole && $requestedRole->name === 'technician') {
            if (!$user->hasRole('technician')) {
                \Illuminate\Support\Facades\Log::warning('User creation: Technician role not assigned, fixing...', [
                    'user_id' => $user->id,
                    'requested_role' => $validated['roles'],
                    'current_roles' => $user->roles->pluck('name')->toArray()
                ]);
                
                // Force assign technician role
                $user->roles()->sync([$requestedRole->id]);
                $user->load('roles');
                
                \Illuminate\Support\Facades\Log::info('User creation: Technician role fixed', [
                    'user_id' => $user->id,
                    'final_roles' => $user->roles->pluck('name')->toArray()
                ]);
            }
        }

        // Generate email verification token
        $verificationToken = Str::random(64);
        $user->email_verification_token = $verificationToken;
        $user->email_verification_token_expires_at = Carbon::now()->addHours(24);

        try {
            $user->save();
            \Illuminate\Support\Facades\Log::info('User saved with verification token');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save user with verification token: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'User created but failed to set up email verification.')
                ->withInput();
        }

        // Create verification URL
        $verificationUrl = route('email.verify', ['token' => $verificationToken]);
        \Illuminate\Support\Facades\Log::info('Verification URL generated: ' . $verificationUrl);

        // Send verification email
        try {
            $emailSent = $this->emailService->sendEmailVerification($user, $verificationUrl);
            \Illuminate\Support\Facades\Log::info('Email sending result: ' . ($emailSent ? 'SUCCESS' : 'FAILED'));
        } catch (\Exception $e) {
            // Log the error but don't fail the user creation
            \Illuminate\Support\Facades\Log::error('Email verification failed during user creation', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            $emailSent = false;
        }

        $message = 'User created successfully.';
        if ($emailSent) {
            $message .= ' Verification email sent to ' . $user->email . '.';
        } else {
            $message .= ' However, failed to send verification email.';
        }

        \Illuminate\Support\Facades\Log::info('User creation completed: ' . $message);

        return redirect()->route('admin.accounts.index')
            ->with('success', $message);
    }


    /**
     * Update the user's roles.
     */
    public function updateRoles(Request $request, User $user)
    {
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        $validated = $request->validate([
            'roles' => 'required|exists:roles,id',
            'direct_permissions' => 'nullable|array',
            'direct_permissions.*' => 'exists:permissions,id',
        ]);

        // Prevent non-admins from assigning admin role
        if (!auth()->user()->is_admin) {
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole && $validated['roles'] == $adminRole->id) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign the admin role.');
            }
        }

        // Update user roles
        $user->roles()->sync([$validated['roles']]);

        // Update direct permissions if provided
        if (isset($validated['direct_permissions'])) {
            $checkedPermissionIds = $validated['direct_permissions'];
            $allPermissionIds = Permission::pluck('id')->toArray();
            $uncheckedPermissionIds = array_diff($allPermissionIds, $checkedPermissionIds);

            // Handle checked permissions
            foreach ($checkedPermissionIds as $permissionId) {
                if ($user->permissions->contains($permissionId)) {
                    $user->permissions()->updateExistingPivot($permissionId, ['is_active' => true]);
                } else {
                    $user->permissions()->attach($permissionId, ['is_active' => true]);
                }
            }

            // Handle unchecked permissions
            foreach ($uncheckedPermissionIds as $permissionId) {
                if ($user->permissions->contains($permissionId)) {
                    $user->permissions()->updateExistingPivot($permissionId, ['is_active' => false]);
                } else {
                    $user->permissions()->attach($permissionId, ['is_active' => false]);
                }
            }
        } else {
            // Disable all direct permissions if none selected
            DB::table('permission_user')->where('user_id', $user->id)->update(['is_active' => false]);
        }

        // Clear any cached permissions
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return redirect()->route('accounts.index')
            ->with('success', 'User roles and permissions updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent non-admins from deleting admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('accounts.index')
                ->with('error', 'You do not have permission to delete admin users.');
        }

        // Prevent users from deleting themselves
        if ($user->id === auth()->user()->id) {
            return redirect()->route('accounts.index')
                ->with('error', 'You cannot delete your own account.');
        }

        try {
            // Delete the user
            $user->delete();

            return redirect()->route('accounts.index')
                ->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('accounts.index')
                ->with('error', 'An error occurred while deleting the user.');
        }
    }

    /**
     * Display the specified user information.
     */
    public function show(User $user)
    {
        // Check if user can view this user's information
        if (!auth()->user()->is_admin && $user->is_admin) {
            abort(403, 'You do not have permission to view this user.');
        }

        // Load user relationships
        $user->load(['campus', 'office']);

        return view('accounts.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // Load relationships
        $user->load(['campus', 'office']);

        $roles = Role::where('name', '!=', 'super-admin')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $allPermissions = Permission::orderBy('group')
            ->orderBy('name')
            ->get();
            
        // Get effective permissions (what the user can actually do)
        $userPermissions = [];
        foreach ($allPermissions as $permission) {
            if ($user->hasPermissionTo($permission->name)) {
                $userPermissions[] = $permission->id;
            }
        }

        $offices = \App\Models\Office::where('is_active', true)->orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();
        $campuses = \App\Models\Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        return view('accounts.edit', compact('user', 'roles', 'allPermissions', 'userPermissions', 'offices', 'userRoles', 'campuses'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|regex:/^[0-9]+$/|max:15',
            'position' => 'required|string|max:255',
            'office_id' => 'required|exists:offices,id',
            'roles' => auth()->user()->is_super_admin ? 'nullable|exists:roles,id' : '',
            'direct_permissions' => 'nullable|array',
            'direct_permissions.*' => 'exists:permissions,id',
        ]);

        // Get the office and derive campus_id
        $office = \App\Models\Office::find($validated['office_id']);
        $campus_id = $office ? $office->campus_id : null;

        // Update user with all information
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'position' => $validated['position'],
            'office_id' => $validated['office_id'],
            'campus_id' => $campus_id,
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Update roles if provided and user is superadmin
        if (isset($validated['roles']) && auth()->user()->is_super_admin) {
            // Prevent non-admins from assigning admin role
            if (!auth()->user()->is_super_admin) {
                $adminRole = Role::where('name', 'admin')->first();
                if ($adminRole && $validated['roles'] == $adminRole->id) {
                    return redirect()->back()
                        ->with('error', 'You do not have permission to assign the admin role.')
                        ->withInput();
                }
            }

            // Check if user is being assigned/removed from staff role
            $staffRole = Role::where('name', 'staff')->first();
            $technicianRole = Role::where('name', 'technician')->first();
            $isBeingAssignedStaff = $staffRole && $validated['roles'] == $staffRole->id;
            $currentlyHasStaffRole = $user->hasRole('staff');
            $isBeingAssignedTechnician = $technicianRole && $validated['roles'] == $technicianRole->id;
            $currentlyHasTechnicianRole = $user->hasRole('technician');

            $user->roles()->sync([$validated['roles']]);
        }

        // Handle direct permissions if provided
        if (isset($validated['direct_permissions'])) {
            $checkedPermissionIds = $validated['direct_permissions'];
            $allPermissionIds = Permission::pluck('id')->toArray();
            $uncheckedPermissionIds = array_diff($allPermissionIds, $checkedPermissionIds);

            // Handle checked permissions
            foreach ($checkedPermissionIds as $permissionId) {
                if ($user->permissions->contains($permissionId)) {
                    $user->permissions()->updateExistingPivot($permissionId, ['is_active' => true]);
                } else {
                    $user->permissions()->attach($permissionId, ['is_active' => true]);
                }
            }

            // Handle unchecked permissions
            foreach ($uncheckedPermissionIds as $permissionId) {
                if ($user->permissions->contains($permissionId)) {
                    $user->permissions()->updateExistingPivot($permissionId, ['is_active' => false]);
                } else {
                    $user->permissions()->attach($permissionId, ['is_active' => false]);
                }
            }
        } else {
            // Disable all direct permissions if none selected
            DB::table('permission_user')->where('user_id', $user->id)->update(['is_active' => false]);
        }

        // Clear any cached permissions
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return redirect()->route('accounts.index')
            ->with('success', 'User updated successfully.');
    }

    public function grantTempAdmin(Request $request, User $user)
    {
        try {
            // Check authentication first
            $authUser = auth()->user();
            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated.'
                ], 401);
            }

            // Only super-admin can perform this action
            if (!$authUser->is_super_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.'
                ], 403);
            }

            // Only technicians can be granted temp admin
            // If they don't have technician role for some reason, add it
            $technicianRole = Role::where('name', 'technician')->first();
            if (!$technicianRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician role not found.'
                ], 500);
            }

            if (!$user->hasRole('technician')) {
                \Illuminate\Support\Facades\Log::info('grantTempAdmin: User missing technician role, adding it', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'current_roles' => $user->roles->pluck('name')->toArray()
                ]);
                
                // Add technician role if missing
                $user->roles()->attach($technicianRole->id, ['expires_at' => null]);
                $user->load('roles'); // Refresh roles
                
                \Illuminate\Support\Facades\Log::info('grantTempAdmin: Technician role added', [
                    'user_id' => $user->id,
                    'updated_roles' => $user->roles->pluck('name')->toArray()
                ]);
            }

            \Illuminate\Support\Facades\Log::info('grantTempAdmin: Granting temp admin to technician', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'current_roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Validate expires_at (must be in the future)
            $validated = $request->validate([
                'expires_at' => 'required|date|after:now',
            ]);

            $expiresAt = \Carbon\Carbon::parse($validated['expires_at']);

            // Get admin role
            $adminRole = Role::where('name', 'admin')->first();
            if (!$adminRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin role not found.'
                ], 500);
            }

            // Check if user already has ACTIVE admin role (not expired)
            $hasActiveAdminRole = $user->roles()
                ->where('role_id', $adminRole->id)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->exists();
            
            if ($hasActiveAdminRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has active admin role'
                ], 400);
            }

            // Clean up any expired admin roles before granting new access
            $user->roles()
                ->where('role_id', $adminRole->id)
                ->where('expires_at', '<=', now())
                ->detach();

            // Assign admin role with expiry
            $user->roles()->attach($adminRole->id, ['expires_at' => $expiresAt]);

            // Refresh the user's roles to ensure they're properly loaded
            $user->load('roles');

            \Illuminate\Support\Facades\Log::info('grantTempAdmin: Temp admin granted successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'final_roles' => $user->roles->pluck('name')->toArray(),
                'expires_at' => $expiresAt->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Temporary admin access granted to {$user->name}. Expires at: {$expiresAt->format('M j, Y g:i A')}"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception caught: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $t) {
            return response()->json([
                'success' => false,
                'message' => 'Throwable caught: ' . $t->getMessage()
            ], 500);
        }
    }
}