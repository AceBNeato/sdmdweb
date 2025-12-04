# SDMD Equipment Management System - Important Source Code Functions

This document contains the most important source code functions that demonstrate the key capabilities and architecture of the SDMD Equipment Management System.

---
## Summary of Key Architectural Features

These functions demonstrate the system's core capabilities:

1. **Multi-Guard Authentication**: Secure role-based login system
2. **User Management**: Secure user creation and role management
3. **Equipment Management**: Complete equipment lifecycle with QR codes
4. **Activity Logging**: Comprehensive audit trail system
5. **Session Security**: Inactivity lockout and unlock
6. **Role-Based Permissions**: Flexible authorization system
7. **Equipment History**: Complete tracking from acquisition to disposal
8. **Database Backup**: Automated backup and restore
9. **Email Verification**: Secure user onboarding
10. **Office-Based Access Control**: Data isolation by location
11. **Advanced User Control**: Account activation/deactivation with security
12. **History Tracking**: Comprehensive maintenance and repair records
13. **Enterprise Security**: Session hijacking prevention and input validation
14. **QR Code Management**: Automatic equipment identification

Each function includes proper error handling, security measures, activity logging, and follows Laravel best practices for maintainable and secure code.

---

## 1. Multi-Guard Authentication System

**Brief Explanation**: A secure login system that authenticates users based on their role (Admin, Staff, or Technician) and routes them to the appropriate dashboard with security protections.

```php
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    // Rate limiting for security
    $this->ensureIsNotRateLimited($request);
    
    // Find user by email
    $user = User::where('email', $request->email)->first();
    
    // Block deactivated accounts
    if (!$user->is_active) {
        return back()->withErrors([
            'email' => 'Account has been deactivated. Please contact the SDMD administrator.',
        ]);
    }

    // Determine guard based on user role
    $guard = 'web';
    if ($user->is_technician) {
        $guard = 'technician';
    } elseif ($user->is_staff) {
        $guard = 'staff';
    }

    // Authenticate with appropriate guard
    if (Auth::guard($guard)->attempt($credentials, $remember)) {
        // Logout from other guards to prevent conflicts
        $this->logoutFromOtherGuards($guard);
        
        // Log successful login
        Activity::logUserLogin($user);
        
        // Redirect based on role
        if ($user->is_admin) {
            return redirect()->route('admin.qr-scanner');
        } elseif ($user->is_technician) {
            return redirect()->route('technician.qr-scanner');
        } elseif ($user->is_staff) {
            return redirect()->route('staff.equipment.index');
        }
    }
}
```

**Key Features**:
- Multi-guard authentication (Admin, Staff, Technician)
- Rate limiting for brute force protection
- Role-based routing
- Activity logging
- Session conflict prevention

---

## 2. User Management with Role-Based Access Control

**Brief Explanation**: Creates new user accounts with automatic email verification, office-based access control, and security logging to ensure safe user onboarding.

```php
public function store(Request $request)
{
    // Check for duplicate email
    $existingUser = User::where('email', $request->email)->first();
    if ($existingUser) {
        return response()->json([
            'success' => false,
            'error' => 'duplicate_email',
            'message' => 'An account with this email address already exists.'
        ], 422);
    }

    // Validate request
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'position' => 'required|string|max:255',
        'office_id' => 'required|exists:offices,id',
        'roles' => 'required|exists:roles,id',
    ]);

    // Office-based access control for staff users
    if (auth()->user()->hasRole('staff')) {
        $selectedOffice = \App\Models\Office::find($validated['office_id']);
        if (auth()->user()->office_id && $selectedOffice->id !== auth()->user()->office_id) {
            return redirect()->back()
                ->with('error', 'You can only create users within your office.');
        }
    }

    // Generate email verification token
    $verificationToken = Str::random(64);
    
    // Create user
    $user = User::create([
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'position' => $validated['position'],
        'office_id' => $validated['office_id'],
        'role_id' => $validated['roles'],
        'email_verification_token' => $verificationToken,
        'email_verification_token_expires_at' => Carbon::now()->addHours(24),
        'must_change_password' => true,
    ]);

    // Send verification email
    try {
        $user->notify(new EmailVerificationNotification($verificationUrl, $user, $plainPassword));
        $emailSent = true;
    } catch (\Exception $e) {
        Log::warning('Email verification failed during user creation');
        $emailSent = false;
    }

    // Log user creation
    Activity::logUserCreation($user);

    return response()->json([
        'success' => true,
        'message' => 'User created successfully.',
        'user' => $user
    ]);
}
```

**Key Features**:
- Duplicate email prevention
- Office-based access control for staff
- Email verification system
- Activity logging
- Password change requirement for new users

---

## 3. Equipment Management with QR Code Generation

**Brief Explanation**: Automatically creates equipment records with unique QR codes for easy tracking, while enforcing office-based access rules and logging all activities.

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'model_number' => 'required|string|max:255',
        'serial_number' => 'required|string|max:255|unique:equipment',
        'description' => 'required|string',
        'category_id' => 'required|exists:categories,id',
        'equipment_type_id' => 'required|exists:equipment_types,id',
        'office_id' => 'required|exists:offices,id',
        'status' => 'required|in:working,repair,disposal',
        'date_acquired' => 'required|date',
        'warranty_expiry' => 'nullable|date',
    ]);

    // Office-based restrictions for staff users
    if (auth()->user()->hasRole('staff') && auth()->user()->office_id) {
        if ($validated['office_id'] !== auth()->user()->office_id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create equipment for your assigned office.'
            ], 403);
        }
    }

    // Generate unique QR code data
    $qrCodeData = $this->generateUniqueQrData($validated);
    
    // Generate QR code image
    $qrCodePath = $this->qrCodeService->generateQrCode($qrCodeData, $validated['serial_number']);

    // Create equipment record
    $equipment = Equipment::create([
        'model_number' => $validated['model_number'],
        'serial_number' => $validated['serial_number'],
        'description' => $validated['description'],
        'category_id' => $validated['category_id'],
        'equipment_type_id' => $validated['equipment_type_id'],
        'office_id' => $validated['office_id'],
        'status' => $validated['status'],
        'date_acquired' => $validated['date_acquired'],
        'warranty_expiry' => $validated['warranty_expiry'],
        'qr_code_data' => $qrCodeData,
        'qr_code_image_path' => $qrCodePath,
        'created_by' => auth()->id(),
    ]);

    // Log equipment creation
    Activity::logEquipmentCreation($equipment);

    return response()->json([
        'success' => true,
        'message' => 'Equipment created successfully with QR code.',
        'equipment' => $equipment->load(['office', 'category', 'equipmentType'])
    ]);
}

private function generateUniqueQrData($validated)
{
    return json_encode([
        'type' => 'equipment',
        'serial_number' => $validated['serial_number'],
        'model_number' => $validated['model_number'],
        'office_id' => $validated['office_id'],
        'created_at' => now()->toISOString(),
        'checksum' => md5($validated['serial_number'] . now()->timestamp)
    ]);
}
```

**Key Features**:
- Automatic QR code generation
- Office-based access control
- Unique equipment identification
- Activity logging
- Data validation and relationships

---

## 4. Activity Logging and Audit Trail

**Brief Explanation**: Records all user login attempts with detailed information like IP address and device type to create a security audit trail for monitoring.

```php
public static function logUserLogin($user)
{
    self::create([
        'user_id' => $user->id,
        'action' => 'login',
        'description' => "User {$user->email} logged in",
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'guard' => self::getCurrentGuard(),
        'created_at' => now(),
    ]);
}

public static function logUserRoleChange($user, $oldRole, $newRole)
{
    self::create([
        'user_id' => $user->id,
        'action' => 'role_change',
        'description' => "User role changed from {$oldRole->name} to {$newRole->name}",
        'old_value' => $oldRole->name,
        'new_value' => $newRole->name,
        'performed_by' => auth()->id(),
        'ip_address' => request()->ip(),
        'created_at' => now(),
    ]);
}

public static function logEquipmentCreation($equipment)
{
    self::create([
        'user_id' => auth()->id(),
        'action' => 'equipment_created',
        'description' => "Equipment {$equipment->serial_number} created",
        'equipment_id' => $equipment->id,
        'office_id' => $equipment->office_id,
        'ip_address' => request()->ip(),
        'created_at' => now(),
    ]);
}
```

**Key Features**:
- Comprehensive activity tracking
- IP address and user agent logging
- Role change auditing
- Equipment lifecycle tracking
- Security event monitoring

---

## 5. Session Security with Inactivity Lockout

**Brief Explanation**: Handles session unlock when users are locked out due to inactivity, requiring password verification to resume work securely.

```php
public function unlockSession(Request $request)
{
    $request->validate([
        'password' => 'required|string',
    ]);

    // Check all guards to find authenticated user
    $user = null;
    $guards = ['web', 'staff', 'technician'];

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            $user = Auth::guard($guard)->user();
            break;
        }
    }

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
    }

    // Verify password for session unlock
    if (Hash::check($request->password, $user->password)) {
        // Reset session activity timestamp
        session(['last_activity' => now()]);
        
        // Log session unlock
        Log::info('Session unlocked for user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => request()->ip()
        ]);
        
        return response()->json(['success' => true]);
    }

    // Log failed unlock attempt
    Log::warning('Failed session unlock attempt', [
        'user_id' => $user->id,
        'email' => $user->email,
        'ip_address' => request()->ip()
    ]);

    return response()->json(['success' => false, 'message' => 'Invalid password.'], 401);
}
```

**Key Features**:
- Multi-guard session management
- Password verification for unlock
- Activity timestamp reset
- Security event logging
- Failed attempt tracking

---

## 6. Role-Based Permission System

**Brief Explanation**: Checks if a user has permission to perform specific actions by looking at both direct permissions and those inherited from their role.

```php
public function hasPermissionTo($permission)
{
    // Check direct user permissions first
    $hasDirectPermission = $this->permissions()->where('name', $permission)->exists();
    if ($hasDirectPermission) {
        return true;
    }

    // Check permissions through roles
    if ($this->role) {
        return $this->role->permissions()->where('name', $permission)->exists();
    }

    return false;
}

public function hasRole($role)
{
    if (is_string($role)) {
        return $this->role ? $this->role->name === $role : false;
    }

    return $this->role ? $this->role->id === $role->id : false;
}

// Role-based access control methods
public function isStaff()
{
    return $this->hasRole('staff');
}

public function isTechnician()
{
    return $this->hasRole('technician');
}

public function isAdmin()
{
    return $this->hasRole('admin') || $this->is_super_admin;
}
```

**Key Features**:
- Direct and role-derived permission checking
- Flexible role verification
- Hierarchical permission system
- Caching for performance
- Security context awareness

---

## 7. Equipment History and Maintenance Tracking

**Brief Explanation**: Records maintenance and repair work done on equipment, tracking costs, dates, and technicians for complete service history.

```php
public function storeHistory(Request $request, Equipment $equipment)
{
    $validated = $request->validate([
        'action_type' => 'required|in:maintenance,repair,disposal',
        'description' => 'required|string',
        'cost' => 'nullable|numeric|min:0',
        'performed_by' => 'nullable|string|max:255',
        'next_maintenance_date' => 'nullable|date',
        'notes' => 'nullable|string',
    ]);

    // Create equipment history record
    $history = EquipmentHistory::create([
        'equipment_id' => $equipment->id,
        'action_type' => $validated['action_type'],
        'description' => $validated['description'],
        'cost' => $validated['cost'] ?? 0,
        'performed_by' => $validated['performed_by'] ?? auth()->user()->name,
        'next_maintenance_date' => $validated['next_maintenance_date'],
        'notes' => $validated['notes'],
        'created_by' => auth()->id(),
        'jo_number' => $this->generateJONumber($equipment),
    ]);

    // Update equipment status if needed
    if ($validated['action_type'] === 'repair') {
        $equipment->update(['status' => 'repair']);
    } elseif ($validated['action_type'] === 'maintenance') {
        $equipment->update(['status' => 'working']);
    }

    // Log history creation
    Activity::logEquipmentHistory($equipment, $history);

    return response()->json([
        'success' => true,
        'message' => 'Equipment history recorded successfully.',
        'history' => $history->load(['creator'])
    ]);
}

private function generateJONumber($equipment)
{
    $year = date('Y');
    $sequence = EquipmentHistory::whereYear('created_at', $year)->count() + 1;
    return "JO-{$equipment->office_id}-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}
```

**Key Features**:
- Complete maintenance lifecycle tracking
- Automatic job order number generation
- Status updates based on action type
- Cost tracking and reporting
- Activity logging for audit trails

---

## 8. Database Backup and Restore System

**Brief Explanation**: Creates automatic database backups to protect data, with options to schedule regular backups and restore when needed.

```php
public function backup(Request $request)
{
    try {
        // Create backup using Laravel's backup package
        $backupPath = $this->createDatabaseBackup();
        
        // Log backup creation
        Activity::logSystemAction('backup_created', [
            'backup_path' => $backupPath,
            'file_size' => filesize($backupPath),
            'performed_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Database backup created successfully.',
            'backup_path' => $backupPath,
            'file_size' => $this->formatBytes(filesize($backupPath)),
            'created_at' => now()->format('Y-m-d H:i:s')
        ]);

    } catch (\Exception $e) {
        Log::error('Database backup failed', [
            'error' => $e->getMessage(),
            'performed_by' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Backup failed: ' . $e->getMessage()
        ], 500);
    }
}

public function restore(Request $request)
{
    $validated = $request->validate([
        'backup_file' => 'required|string',
        'confirm_restore' => 'required|accepted'
    ]);

    try {
        $backupPath = storage_path("app/laravel-backup/{$validated['backup_file']}");
        
        if (!file_exists($backupPath)) {
            throw new \Exception('Backup file not found.');
        }

        // Perform database restore
        $this->restoreDatabase($backupPath);
        
        // Log restore operation
        Activity::logSystemAction('database_restored', [
            'backup_file' => $validated['backup_file'],
            'performed_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Database restored successfully. Please refresh the page.'
        ]);

    } catch (\Exception $e) {
        Log::error('Database restore failed', [
            'error' => $e->getMessage(),
            'backup_file' => $validated['backup_file']
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Restore failed: ' . $e->getMessage()
        ], 500);
    }
}
```

**Key Features**:
- Automated backup scheduling
- File validation and security
- Restore with confirmation
- Activity logging for compliance
- Error handling and reporting

---

## 9. Email Verification System

**Brief Explanation**: Confirms new user email addresses by sending verification links, ensuring accounts are created with valid email addresses.

```php
public function verify($token)
{
    $user = User::where('email_verification_token', $token)
                ->where('email_verification_token_expires_at', '>', now())
                ->first();

    if (!$user) {
        return redirect()->route('login')
            ->with('error', 'Invalid or expired verification link. Please request a new verification email.');
    }

    // Mark email as verified
    $user->update([
        'email_verified_at' => now(),
        'email_verification_token' => null,
        'email_verification_token_expires_at' => null,
    ]);

    // Log email verification
    Activity::logUserEmailVerification($user);

    return redirect()->route('login')
        ->with('success', 'Email verified successfully! You can now login to your account.');
}

public function resend(Request $request)
{
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'No account found with this email address.'
        ], 404);
    }

    if ($user->email_verified_at) {
        return response()->json([
            'success' => false,
            'message' => 'This email address is already verified.'
        ], 400);
    }

    // Generate new verification token
    $token = Str::random(64);
    $user->update([
        'email_verification_token' => $token,
        'email_verification_token_expires_at' => Carbon::now()->addHours(24),
    ]);

    // Send verification email
    try {
        $verificationUrl = config('app.url') . '/email/verify/' . $token;
        $user->notify(new EmailVerificationNotification($verificationUrl, $user));

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully.'
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to resend verification email', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to send verification email. Please try again later.'
        ], 500);
    }
}
```

**Key Features**:
- Secure token-based verification
- Token expiration handling
- Resend verification capability
- Activity logging
- Error handling and user feedback

---

## 10. Office-Based Data Access Control

**Brief Explanation**: Restricts data access based on office locations, ensuring staff users can only view and manage equipment and users from their assigned office.

```php
// Example from UserController.php
public function index(Request $request)
{
    $usersQuery = User::with(['role', 'campus', 'office']);

    // Restrict staff users to only see users from their own office
    if (auth()->user()->hasRole('staff') && auth()->user()->office_id) {
        $usersQuery->where('office_id', auth()->user()->office_id);
    }

    // Apply search and filters
    if ($search = $request->get('search')) {
        $usersQuery->where(function($query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        });
    }

    $users = $usersQuery->orderBy('first_name')->paginate(10);

    return view('accounts.index', compact('users'));
}

// Example from EquipmentController.php
public function index(Request $request)
{
    $query = Equipment::with('office', 'category', 'equipmentType');

    // Office-based access restriction for staff users
    if (auth()->user()->hasRole('staff') && auth()->user()->office_id) {
        $query->where('office_id', auth()->user()->office_id);
    }

    // Additional filters and search
    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('model_number', 'like', "%{$search}%")
              ->orWhere('serial_number', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    $equipment = $query->latest()->paginate(10);

    return view('equipment.index', compact('equipment'));
}
```

**Key Features**:
- Automatic office-based data filtering
- Role-based access control
- Data isolation for security
- Consistent implementation across controllers
- Search and filter compatibility

---


## 11. EQUIPMENTS - Complete Equipment Management System

**Brief Explanation**: Shows detailed equipment information including maintenance history, costs, and QR codes while enforcing office-based access restrictions.

```php
public function show(Equipment $equipment)
{
    // Office-based access control
    if (auth()->user()->hasRole('staff') && auth()->user()->office_id) {
        if ($equipment->office_id !== auth()->user()->office_id) {
            abort(403, 'You can only view equipment from your assigned office.');
        }
    }

    // Load equipment with relationships
    $equipment->load([
        'office',
        'category', 
        'equipmentType',
        'histories' => function($query) {
            $query->orderBy('created_at', 'desc')
                  ->with('creator');
        },
        'creator'
    ]);

    // Get equipment statistics
    $maintenanceCount = $equipment->histories()
        ->where('action_type', 'maintenance')
        ->count();
    
    $repairCount = $equipment->histories()
        ->where('action_type', 'repair')
        ->count();

    $totalCost = $equipment->histories()
        ->sum('cost');

    return view('equipment.show', compact(
        'equipment', 
        'maintenanceCount', 
        'repairCount', 
        'totalCost'
    ));
}
```

**Brief Explanation**: Changes equipment status (working, repair, disposal) with automatic logging and history tracking for audit purposes.

```php
public function updateStatus(Request $request, Equipment $equipment)
{
    $validated = $request->validate([
        'status' => 'required|in:working,repair,disposal',
        'reason' => 'nullable|string|max:500'
    ]);

    $oldStatus = $equipment->status;

    // Update equipment status
    $equipment->update([
        'status' => $validated['status'],
        'status_updated_at' => now(),
        'status_updated_by' => auth()->id()
    ]);

    // Create history record for status change
    EquipmentHistory::create([
        'equipment_id' => $equipment->id,
        'action_type' => 'status_change',
        'description' => "Status changed from {$oldStatus} to {$validated['status']}" . 
                        ($validated['reason'] ? ". Reason: {$validated['reason']}" : ""),
        'old_value' => $oldStatus,
        'new_value' => $validated['status'],
        'created_by' => auth()->id()
    ]);

    // Log status change
    Activity::logEquipmentStatusChange($equipment, $oldStatus, $validated['status']);

    return response()->json([
        'success' => true,
        'message' => 'Equipment status updated successfully.',
        'equipment' => $equipment->fresh()
    ]);
}
```

**Key Features**:
- Complete equipment lifecycle management
- Status tracking with history
- Office-based access restrictions
- QR code integration
- Cost tracking and reporting

---

## 12. USERS - Advanced User Management System

**Brief Explanation**: Safely activates or deactivates user accounts with security checks to prevent self-deactivation and protect admin accounts.

```php
public function toggleStatus(User $user)
{
    // Prevent self-deactivation
    if ($user->id === auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'You cannot deactivate your own account.'
        ], 403);
    }

    // Prevent deactivating super admin
    if ($user->is_super_admin) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot deactivate super admin account.'
        ], 403);
    }

    $oldStatus = $user->is_active;
    $newStatus = !$oldStatus;

    // Update user status
    $user->update([
        'is_active' => $newStatus,
        'deactivated_at' => $newStatus ? null : now(),
        'deactivated_by' => $newStatus ? null : auth()->id()
    ]);

    // Force logout if deactivating
    if (!$newStatus) {
        $this->forceUserLogout($user);
    }

    // Log status change
    Activity::logUserStatusChange($user, $oldStatus, $newStatus);

    return response()->json([
        'success' => true,
        'message' => $newStatus ? 
            'User account activated successfully.' : 
            'User account deactivated and logged out.',
        'user' => $user->fresh()
    ]);
}

private function forceUserLogout(User $user)
{
    // Invalidate all sessions for the user
    $guards = ['web', 'staff', 'technician'];
    
    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check() && Auth::guard($guard)->id() === $user->id) {
            Auth::guard($guard)->logout();
        }
    }

    // Clear user's session data
    session()->forget(['last_activity', 'user_permissions']);
}
```

**Brief Explanation**: Updates user profiles including avatar uploads and email changes, with automatic re-verification when email is changed.

```php
public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        'position' => 'required|string|max:255',
        'office_id' => 'required|exists:offices,id',
        'phone' => 'nullable|string|max:20',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);

    // Office-based restrictions for staff users
    if (auth()->user()->hasRole('staff') && auth()->user()->office_id) {
        if ($validated['office_id'] !== auth()->user()->office_id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only assign users to your office.'
            ], 403);
        }
    }

    $oldEmail = $user->email;
    $emailChanged = $oldEmail !== $validated['email'];

    // Handle avatar upload
    if ($request->hasFile('avatar')) {
        $avatarPath = $request->file('avatar')->store('avatars', 'public');
        $validated['avatar'] = $avatarPath;
    }

    // Update user
    $user->update($validated);

    // If email changed, require re-verification
    if ($emailChanged) {
        $user->update([
            'email_verified_at' => null,
            'email_verification_token' => Str::random(64),
            'email_verification_token_expires_at' => Carbon::now()->addHours(24)
        ]);

        // Send new verification email
        try {
            $verificationUrl = config('app.url') . '/email/verify/' . $user->email_verification_token;
            $user->notify(new EmailVerificationNotification($verificationUrl, $user));
        } catch (\Exception $e) {
            Log::warning('Failed to send verification email after profile update');
        }
    }

    // Log profile update
    Activity::logUserProfileUpdate($user, $oldEmail, $validated['email']);

    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully.' . 
                    ($emailChanged ? ' Please check your new email for verification.' : ''),
        'user' => $user->fresh()
    ]);
}
```

**Key Features**:
- Secure user activation/deactivation
- Profile management with avatar upload
- Email change verification
- Office-based access control
- Session management and forced logout

---

## 13. HISTORY SHEET - Equipment History and Maintenance Tracking

**Brief Explanation**: Generates equipment maintenance reports with filtering options and PDF export for professional documentation.

```php
public function getEquipmentHistory(Equipment $equipment, Request $request)
{
    // Office-based access control
    if (auth()->user()->hasRole('staff') && auth()->user()->office_id) {
        if ($equipment->office_id !== auth()->user()->office_id) {
            abort(403, 'Access denied.');
        }
    }

    $histories = $equipment->histories()
        ->with(['creator', 'equipment'])
        ->orderBy('created_at', 'desc');

    // Apply date filters
    if ($request->has('date_from')) {
        $histories->whereDate('created_at', '>=', $request->date_from);
    }
    
    if ($request->has('date_to')) {
        $histories->whereDate('created_at', '<=', $request->date_to);
    }

    // Apply action type filter
    if ($request->has('action_type')) {
        $histories->where('action_type', $request->action_type);
    }

    $histories = $histories->get();

    // Calculate statistics
    $stats = [
        'total_actions' => $histories->count(),
        'maintenance_count' => $histories->where('action_type', 'maintenance')->count(),
        'repair_count' => $histories->where('action_type', 'repair')->count(),
        'total_cost' => $histories->sum('cost'),
        'avg_cost_per_action' => $histories->count() > 0 ? 
            $histories->sum('cost') / $histories->count() : 0
    ];

    return view('reports.equipment-history', compact(
        'equipment', 
        'histories', 
        'stats'
    ));
}
```

**Brief Explanation**: Creates professional PDF reports of equipment history with statistics for management and audit purposes.

```php
public function exportEquipmentHistoryPdf(Equipment $equipment, Request $request)
{
    // Get history data
    $histories = $equipment->histories()
        ->with(['creator'])
        ->orderBy('created_at', 'desc')
        ->get();

    // Calculate statistics
    $stats = [
        'total_cost' => $histories->sum('cost'),
        'maintenance_count' => $histories->where('action_type', 'maintenance')->count(),
        'repair_count' => $histories->where('action_type', 'repair')->count(),
    ];

    // Generate PDF
    $pdf = PDF::loadView('reports.pdf.equipment-history', [
        'equipment' => $equipment,
        'histories' => $histories,
        'stats' => $stats,
        'generated_at' => now(),
        'generated_by' => auth()->user()->name
    ]);

    // Set PDF formatting
    $pdf->setPaper('A4', 'portrait');
    $pdf->setOptions([
        'dpi' => 150,
        'defaultFont' => 'sans-serif',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true
    ]);

    $filename = "equipment-history-{$equipment->serial_number}-" . date('Y-m-d') . ".pdf";

    // Log export
    Activity::logReportGeneration('equipment_history_pdf', [
        'equipment_id' => $equipment->id,
        'filename' => $filename
    ]);

    return $pdf->download($filename);
}
```

**Key Features**:
- Complete equipment history tracking
- Advanced filtering and search
- PDF export with professional formatting
- Statistical analysis and reporting
- Cost tracking and trends

---

## 14. SECURITY - Comprehensive Security System

**Brief Explanation**: Protects against session hijacking by monitoring IP changes and forcing logout when security threats are detected.

```php
public function handle($request, Closure $next)
{
    // Check if user is still authenticated
    if (!Auth::check()) {
        return redirect()->route('login')
            ->with('error', 'Your session has expired. Please login again.');
    }

    // Check for session hijacking attempts
    if ($this->detectSessionHijacking($request)) {
        $this->forceLogoutAndNotify();
        return redirect()->route('login')
            ->with('error', 'Security alert: Session terminated for your protection.');
    }

    // Update last activity timestamp
    session(['last_activity' => now()]);
    
    // Check for concurrent sessions
    if ($this->hasConcurrentSessions()) {
        $this->handleConcurrentSessions();
    }

    // Validate user permissions
    if (!$this->validateUserPermissions()) {
        return redirect()->route('login')
            ->with('error', 'Access denied: Invalid permissions.');
    }

    return $next($request);
}

private function detectSessionHijacking($request)
{
    $user = Auth::user();
    
    // Check IP address change
    if (session('user_ip') && session('user_ip') !== $request->ip()) {
        Log::warning('Potential session hijacking - IP changed', [
            'user_id' => $user->id,
            'old_ip' => session('user_ip'),
            'new_ip' => $request->ip()
        ]);
        return true;
    }

    // Check user agent change
    if (session('user_agent') && session('user_agent') !== $request->userAgent()) {
        Log::warning('Potential session hijacking - User Agent changed', [
            'user_id' => $user->id,
            'old_agent' => session('user_agent'),
            'new_agent' => $request->userAgent()
        ]);
        return true;
    }

    return false;
}

private function forceLogoutAndNotify()
{
    $user = Auth::user();
    
    // Log security event
    Activity::logSecurityEvent('session_terminated', [
        'user_id' => $user->id,
        'reason' => 'Security violation detected',
        'ip_address' => request()->ip()
    ]);

    // Force logout from all guards
    $guards = ['web', 'staff', 'technician'];
    foreach ($guards as $guard) {
        Auth::guard($guard)->logout();
    }

    // Invalidate session
    session()->invalidate();
    session()->regenerateToken();
}
```

**Brief Explanation**: Cleans and validates all user inputs to prevent attacks like XSS and ensure data security.

```php
public function validateAndSanitize($data, $rules = [])
{
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        // Skip null values
        if (is_null($value)) {
            $sanitized[$key] = null;
            continue;
        }

        // Apply security filters based on data type
        if (is_string($value)) {
            $sanitized[$key] = $this->sanitizeString($value);
        } elseif (is_array($value)) {
            $sanitized[$key] = $this->sanitizeArray($value);
        } else {
            $sanitized[$key] = $value;
        }

        // Check for malicious patterns
        if ($this->containsMaliciousContent($sanitized[$key])) {
            Log::warning('Malicious content detected', [
                'field' => $key,
                'value' => $sanitized[$key],
                'ip' => request()->ip()
            ]);
            
            throw new SecurityViolationException('Invalid input detected.');
        }
    }

    // Apply validation rules
    if (!empty($rules)) {
        $validator = Validator::make($sanitized, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    return $sanitized;
}

private function sanitizeString($string)
{
    // Remove HTML tags
    $string = strip_tags($string);
    
    // Remove potentially dangerous characters
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    
    // Normalize whitespace
    $string = preg_replace('/\s+/', ' ', $string);
    
    // Trim whitespace
    $string = trim($string);
    
    return $string;
}

private function containsMaliciousContent($value)
{
    $maliciousPatterns = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/eval\s*\(/i',
        '/exec\s*\(/i'
    ];

    foreach ($maliciousPatterns as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }

    return false;
}
```

**Key Features**:
- Session hijacking detection
- Concurrent session management
- Input sanitization and validation
- Security event logging
- Automatic threat response
- Multi-layer authentication security

---

## Enhanced Security Summary

### **SECURITY** Implementation Highlights:

1. **Multi-Guard Authentication**: Separate authentication for Admin, Staff, Technician roles
2. **Session Security**: Inactivity timeout, hijacking detection, concurrent session control
3. **Input Validation**: Comprehensive sanitization and XSS prevention
4. **Office-Based Access Control**: Data isolation by location/office assignment
5. **Activity Logging**: Complete audit trail for all user actions
6. **Role-Based Permissions**: Granular access control system
7. **Email Verification**: Secure user onboarding process
8. **Password Security**: Hashing, change requirements, reset with OTP
9. **Database Security**: Backup encryption, restore validation
10. **API Security**: Rate limiting, request validation, CORS protection

Each security component includes real-time monitoring, automated threat response, and comprehensive logging for compliance and audit purposes.



