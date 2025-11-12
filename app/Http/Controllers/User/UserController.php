<?php

namespace App\Http\Controllers\User;

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
            ->when(!auth()->user()->is_admin, function($query) {
                // Non-admin users can only see non-admin users
                return $query->whereDoesntHave('roles', function($q) {
                    $q->where('name', 'admin');
                });
            })
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

        // Apply route-based role filtering
        $routeName = request()->route()->getName();
        if (str_contains($routeName, 'staff.')) {
            $staffRole = Role::where('name', 'staff')->first();
            if ($staffRole) {
                $usersQuery->whereHas('roles', function($query) use ($staffRole) {
                    $query->where('role_id', $staffRole->id);
                });
            }
        } elseif (str_contains($routeName, 'technicians.')) {
            $technicianRole = Role::where('name', 'technician')->first();
            if ($technicianRole) {
                $usersQuery->whereHas('roles', function($query) use ($technicianRole) {
                    $query->where('role_id', $technicianRole->id);
                });
            }
        }

        $users = $usersQuery->orderBy('first_name')->paginate(10);

        // Get filter options
        $campuses = \App\Models\Campus::where('is_active', true)->orderBy('name')->get();
        $offices = \App\Models\Office::where('is_active', true)->orderBy('name')->get();
        $roles = Role::when(!auth()->user()->is_admin, function($query) {
                // Non-admin users can only filter by non-admin roles
                return $query->where('name', '!=', 'admin');
            })
            ->orderBy('name')
            ->get();

        return view('accounts.index', compact('users', 'campuses', 'offices', 'roles', 'search', 'campusFilter', 'officeFilter', 'roleFilter', 'statusFilter'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::when(!auth()->user()->is_admin, function($query) {
            // Non-admin users can only assign non-admin roles
            return $query->where('name', '!=', 'admin');
        })
        ->where('name', '!=', 'super-admin')
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
        if (!auth()->user()->is_admin) {
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

        \Illuminate\Support\Facades\Log::info('User created with ID: ' . $userId);

        // Get the created user
        $user = User::find($userId);

        if (!$user) {
            \Illuminate\Support\Facades\Log::error('User not found after creation, ID: ' . $userId);
            return redirect()->back()
                ->with('error', 'User was created but could not be retrieved. Please contact administrator.')
                ->withInput();
        }

        \Illuminate\Support\Facades\Log::info('User found: ' . $user->id . ' - ' . $user->email);

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

        return redirect()->route('accounts.index')
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
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        // Load relationships
        $user->load(['campus', 'office']);

        $roles = Role::when(!auth()->user()->is_admin, function($query) {
                // Non-admin users can only assign non-admin roles
                return $query->where('name', '!=', 'admin');
            })
            ->where('name', '!=', 'super-admin')
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
    /**
     * Toggle the active status of a user.
     */
    public function toggleStatus(User $user)
    {
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "User {$status} successfully.");
    }

    /**
     * Toggle the admin status of a user.
     */
    public function toggleAdmin(User $user)
    {
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        $user->update(['is_admin' => !$user->is_admin]);

        $status = $user->is_admin ? 'granted admin privileges' : 'revoked admin privileges';
        return redirect()->back()->with('success', "User {$status} successfully.");
    }
    }