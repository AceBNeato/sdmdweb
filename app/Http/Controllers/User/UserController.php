<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\StoredProcedureService;
use App\Services\EmailService;
use App\Notifications\EmailVerificationNotification;
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

        // Restrict staff users to only see users from their own office
        if (auth()->user()->hasRole('staff') && auth()->user()->office_id) {
            $usersQuery->where('office_id', auth()->user()->office_id);
        }

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

        // For staff users, restrict filter options to their campus/office
        if (auth()->user()->hasRole('staff')) {
            if (auth()->user()->campus_id) {
                $campuses = $campuses->where('id', auth()->user()->campus_id);
                $offices = $offices->where('campus_id', auth()->user()->campus_id);
            }
            if (auth()->user()->office_id) {
                $offices = $offices->where('id', auth()->user()->office_id);
            }
        }

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

        // For staff users, restrict office options to their campus/office
        if (auth()->user()->hasRole('staff')) {
            if (auth()->user()->campus_id) {
                $campuses = $campuses->where('id', auth()->user()->campus_id);
                $offices = $offices->where('campus_id', auth()->user()->campus_id);
            }
            if (auth()->user()->office_id) {
                $offices = $offices->where('id', auth()->user()->office_id);
            }
        }

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

        // For staff users, validate that the selected office is within their campus/office
        if (auth()->user()->hasRole('staff')) {
            $selectedOffice = \App\Models\Office::find($validated['office_id']);
            if ($selectedOffice) {
                if (auth()->user()->campus_id && $selectedOffice->campus_id !== auth()->user()->campus_id) {
                    return redirect()->back()
                        ->with('error', 'You can only create users within your campus.')
                        ->withInput();
                }
                if (auth()->user()->office_id && $selectedOffice->id !== auth()->user()->office_id) {
                    return redirect()->back()
                        ->with('error', 'You can only create users within your office.')
                        ->withInput();
                }
            }
        }

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
            $user->notify(new EmailVerificationNotification($verificationUrl, $user));
            \Illuminate\Support\Facades\Log::info('Email verification notification sent successfully');
            $emailSent = true;
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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.accounts.index'),
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'position' => $user->position,
                    'is_active' => $user->is_active
                ]
            ]);
        }

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
            return redirect()->route('admin.accounts.index')
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
        // Log role change
        $oldRole = $user->roles->first();
        $newRole = Role::find($validated['roles']);

        \Illuminate\Support\Facades\Log::info('User role updated via updateRoles method', [
            'admin_user_id' => auth()->id(),
            'admin_user_email' => auth()->user()->email,
            'affected_user_id' => $user->id,
            'affected_user_email' => $user->email,
            'old_role_id' => $oldRole ? $oldRole->id : null,
            'old_role_name' => $oldRole ? $oldRole->name : 'none',
            'new_role_id' => $newRole ? $newRole->id : null,
            'new_role_name' => $newRole ? $newRole->name : 'none',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        $user->roles()->sync([$validated['roles']]);

        // Force logout if this user is currently logged in
        $this->forceUserLogout($user);

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

        return redirect()->route('admin.accounts.index')
            ->with('success', 'User roles and permissions updated successfully. User has been automatically logged out for security.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent non-admins from deleting admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'You do not have permission to delete admin users.');
        }

        // Prevent users from deleting themselves
        if ($user->id === auth()->user()->id) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Require current password confirmation
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->input('password'), auth()->user()->password)) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'Password confirmation failed. User was not deleted.');
        }

        try {
            DB::transaction(function () use ($user) {
                // Detach related data before permanent deletion
                $user->roles()->detach();
                $user->permissions()->detach();

                // Permanently remove the user
                $user->forceDelete();
            });

            return redirect()->route('admin.accounts.index')
                ->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('admin.accounts.index')
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

        // Prevent staff users from viewing users outside their office
        if (auth()->user()->hasRole('staff') && auth()->user()->office_id && $user->office_id !== auth()->user()->office_id) {
            abort(403, 'You can only view users within your office.');
        }

        // Load user relationships
        $user->load(['campus', 'office']);

        if (request()->ajax()) {
            // Return partial view for modal
            return view('accounts.show_modal', compact('user'));
        }

        return view('accounts.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        // Prevent staff users from editing users outside their office
        if (auth()->user()->hasRole('staff') && auth()->user()->office_id && $user->office_id !== auth()->user()->office_id) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'You can only edit users within your office.');
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

        // Get direct permissions only (permissions assigned directly to user)
        $directPermissions = $user->permissions->where('pivot.is_active', true)->pluck('id')->toArray();

        $offices = \App\Models\Office::where('is_active', true)->orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();
        $campuses = \App\Models\Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        // For staff users, restrict office options to their campus/office
        if (auth()->user()->hasRole('staff')) {
            if (auth()->user()->campus_id) {
                $campuses = $campuses->where('id', auth()->user()->campus_id);
                $offices = $offices->where('campus_id', auth()->user()->campus_id);
            }
            if (auth()->user()->office_id) {
                $offices = $offices->where('id', auth()->user()->office_id);
            }
        }

        $viewData = compact('user', 'roles', 'allPermissions', 'userPermissions', 'directPermissions', 'offices', 'userRoles', 'campuses');

        if (request()->ajax() || request()->wantsJson() || request()->boolean('modal')) {
            return view('accounts.edit_modal', $viewData);
        }

        return view('accounts.edit', $viewData);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        // Prevent staff users from updating users outside their office
        if (auth()->user()->hasRole('staff') && auth()->user()->office_id && $user->office_id !== auth()->user()->office_id) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'You can only update users within your office.');
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

        // For staff users, validate that the selected office is within their campus/office
        if (auth()->user()->hasRole('staff')) {
            $selectedOffice = \App\Models\Office::find($validated['office_id']);
            if ($selectedOffice) {
                if (auth()->user()->campus_id && $selectedOffice->campus_id !== auth()->user()->campus_id) {
                    return redirect()->back()
                        ->with('error', 'You can only assign users to offices within your campus.')
                        ->withInput();
                }
                if (auth()->user()->office_id && $selectedOffice->id !== auth()->user()->office_id) {
                    return redirect()->back()
                        ->with('error', 'You can only assign users to your office.')
                        ->withInput();
                }
            }
        }

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
        $roleChanged = false;
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

            // Check if role actually changed
            $currentRoleId = $user->roles->first()?->id;
            $roleChanged = $currentRoleId != $validated['roles'];

            // Log role change
            $oldRole = $user->roles->first();
            $newRole = Role::find($validated['roles']);

            \Illuminate\Support\Facades\Log::info('User role updated', [
                'admin_user_id' => auth()->id(),
                'admin_user_email' => auth()->user()->email,
                'affected_user_id' => $user->id,
                'affected_user_email' => $user->email,
                'old_role_id' => $oldRole ? $oldRole->id : null,
                'old_role_name' => $oldRole ? $oldRole->name : 'none',
                'new_role_id' => $newRole ? $newRole->id : null,
                'new_role_name' => $newRole ? $newRole->name : 'none',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            $user->roles()->sync([$validated['roles']]);

            // Force logout and redirect if this user is currently logged in and role changed
            if ($roleChanged) {
                $this->forceUserLogout($user);
                // If this is the current user being updated, redirect to login
                if (auth()->check() && auth()->id() === $user->id) {
                    Auth::logout();
                    return redirect()->route('login')->with('warning', 'Your role has been changed by an administrator. Please login again with your new role.');
                }
            }
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

        // Check if this is the current user who had their role changed
        $currentUserRoleChanged = $roleChanged && auth()->check() && auth()->id() === $user->id;

        if ($request->ajax() || $request->wantsJson()) {
            if ($currentUserRoleChanged) {
                // Current user had role changed, return special response
                return response()->json([
                    'success' => true,
                    'message' => '⚠️ Your role has been changed! You will be logged out and redirected to login.',
                    'alert_type' => 'warning',
                    'redirect' => route('login'),
                    'force_logout' => true,
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'position' => $user->position,
                        'is_active' => $user->is_active
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'redirect' => route('admin.accounts.index'),
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'position' => $user->position,
                    'is_active' => $user->is_active
                ]
            ]);
        }

        $message = 'User updated successfully.';
        if ($roleChanged) {
            if ($currentUserRoleChanged) {
                // Current user had role changed - they were already redirected above
                return;
            }
            $message .= ' User has been automatically logged out due to role change for security.';
        }

        return redirect()->route('admin.accounts.index')
            ->with('success', $message);
    }

    /**
     * Force logout a user from all guards if they are currently logged in
     */
    private function forceUserLogout(User $user)
    {
        $guards = ['web', 'technician', 'staff'];

        $loggedOut = false;
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check() && Auth::guard($guard)->id() === $user->id) {
                Auth::guard($guard)->logout();
                $loggedOut = true;
            }
        }

        // Always clear all sessions for this user to ensure complete logout
        if ($loggedOut || true) { // Always clear sessions when role changes for safety
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }
    }

    /**
     * Toggle the active status of a user.
     */
    public function toggleStatus(User $user)
    {
        // Prevent non-admins from editing admin users
        if ($user->hasRole('admin') && !auth()->user()->is_admin) {
            return redirect()->route('admin.accounts.index')
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
            return redirect()->route('admin.accounts.index')
                ->with('error', 'You do not have permission to edit admin users.');
        }

        $user->update(['is_admin' => !$user->is_admin]);

        $status = $user->is_admin ? 'granted admin privileges' : 'revoked admin privileges';
        return redirect()->back()->with('success', "User {$status} successfully.");
    }
    }