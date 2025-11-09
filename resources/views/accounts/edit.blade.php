@extends('layouts.app')

@section('title', 'Edit User')
@section('breadcrumbs', 'Accounts / User Management / Edit User')

@push('styles')

<link rel="stylesheet" href="{{ asset('css/accounts/accounts.css') }}">

<style>
    .form-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .form-header {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .form-body {
        padding: 2rem;
    }

    .form-section {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
    }

    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-section-title i {
        color: #4299e1;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 500;
        color: #4a5568;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .form-group.required label::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }

    .form-control, .form-select {
        height: 42px;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        border: 1px solid #d1d5db;
        padding: 0.5rem 0.75rem;
        transition: all 0.15s ease-in-out;
        background-color: white;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4299e1;
        outline: 0;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        background-color: white;
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .invalid-feedback {
        color: #dc3545;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .form-text {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    /* Password Strength Indicator */
    .password-strength {
        margin-top: 0.5rem;
    }

    .strength-meter {
        height: 4px;
        background-color: #e2e8f0;
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .strength-bar {
        height: 100%;
        transition: all 0.3s ease;
        width: 0%;
    }

    .strength-weak { background-color: #dc3545; width: 25%; }
    .strength-fair { background-color: #ffc107; width: 50%; }
    .strength-good { background-color: #17a2b8; width: 75%; }
    .strength-strong { background-color: #28a745; width: 100%; }

    .strength-text {
        font-size: 0.75rem;
        color: #6c757d;
    }

    /* Roles Section */
    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .role-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .role-card:hover {
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }

    .role-card.muted {
        opacity: 0.5;
        pointer-events: none;
    }

    .role-card input[type="checkbox"] {
        margin-right: 0.5rem;
    }

    .role-card label {
        font-weight: 500;
        color: #4a5568;
        cursor: pointer;
        margin-bottom: 0;
    }

    /* Permissions Section */
    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .permission-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .permission-card:hover {
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }

    .permission-card input[type="checkbox"] {
        margin-right: 0.5rem;
    }

    .permission-card label {
        font-weight: 500;
        color: #4a5568;
        cursor: pointer;
        margin-bottom: 0;
    }

    /* Form Actions */
    .form-actions {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        border: 1px solid transparent;
        transition: all 0.15s ease-in-out;
        text-decoration: none;
        cursor: pointer;
    }

    .btn i {
        font-size: 1rem;
    }

    .btn-primary {
        background-color: #4299e1;
        border-color: #4299e1;
        color: white;
    }

    .btn-primary:hover {
        background-color: #3182ce;
        border-color: #2c5282;
        transform: translateY(-1px);
    }

    .btn-outline-secondary {
        color: #4a5568;
        border-color: #d1d5db;
        background-color: white;
    }

    .btn-outline-secondary:hover {
        background-color: #f8fafc;
        border-color: #9ca3af;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .form-body {
            padding: 1rem;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .roles-grid {
            grid-template-columns: 1fr;
        }

        .permissions-grid {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .form-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Alert Styles */
    .alert {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .alert i {
        margin-right: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="content">
    <div class="action-buttons">
        <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
            <i class='bx bx-arrow-back me-1'></i> Back to Accounts
        </a>
    </div>

    <div class="form-card">
        <div class="form-header">
            <h5 class="mb-0">Edit User Information</h5>
            <p class="text-sm text-gray-600 mb-0">Update user details, password, and roles</p>
        </div>

        <form action="{{ route('accounts.update', $user) }}" method="POST" class="needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="form-body">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h6 class="form-section-title">
                        <i class='bx bx-user'></i>
                        Basic Information
                    </h6>

                    <div class="form-row">
                        <div class="form-group required">
                            <label for="name">Full Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $user->name) }}" required
                                   placeholder="Enter full name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group required">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email', $user->email) }}" required
                                   placeholder="Enter email address">
                            @error('email')
                                <div clFRass="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group required">
                            <label for="position">Position</label>
                            <input type="text" class="form-control @error('position') is-invalid @enderror"
                                   id="position" name="position" value="{{ old('position', $user->position) }}" required
                                   placeholder="Job title or position">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text">Enter the user's job title or position</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                                   pattern="[0-9]*" placeholder="09123456789">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text">Mobile number (digits only, optional)</small>
                        </div>
                    </div>
                </div>

                <!-- Account Security Section -->
                <div class="form-section">
                    <h6 class="form-section-title">
                        <i class='bx bx-lock'></i>
                        Account Security
                    </h6>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password"
                                   placeholder="Enter new password (leave blank to keep current)">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text">Leave blank to keep current password</small>

                            <!-- Password Strength Indicator -->
                            <div class="password-strength" id="password-strength" style="display: none;">
                                <div class="strength-meter">
                                    <div class="strength-bar" id="strength-bar"></div>
                                </div>
                                <div class="strength-text" id="strength-text">Password strength: </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input type="password" class="form-control"
                                   id="password_confirmation" name="password_confirmation"
                                   placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <!-- Organization Section -->
                <div class="form-section">
                    <h6 class="form-section-title">
                        <i class='bx bx-building'></i>
                        Organization
                    </h6>

                    <div class="form-row">
                        <div class="form-group required">
                            <label for="office_id">Office</label>
                            <select class="form-select @error('office_id') is-invalid @enderror"
                                    id="office_id" name="office_id" required>
                                <option value="">Select Office</option>
                                @foreach(\App\Models\Campus::where('is_active', true)->orderBy('name')->get() as $campus)
                                    <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                        @foreach($campus->offices->where('is_active', true) as $office)
                                            <option value="{{ $office->id }}" {{ (old('office_id', $user->office_id) == $office->id) ? 'selected' : '' }}>
                                                {{ $office->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('office_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

            

                <!-- Direct Permissions Section -->
                <div class="form-section">
                    <h6 class="form-section-title">
                        <i class='bx bx-key'></i>
                        Direct Permissions
                    </h6>

                    <div class="mb-4">
                        <div class="permissions-grid">
                            @foreach($allPermissions as $permission)
                                <div class="permission-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="direct_permissions[]"
                                               value="{{ $permission->id }}"
                                               id="permission_{{ $permission->id }}"
                                               {{ in_array($permission->id, $userPermissions ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            {{ $permission->display_name ?? $permission->name }}
                                        </label>
                                    </div>
                                    @if($permission->description)
                                        <small class="form-text">{{ $permission->description }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <small class="form-text text-muted">Grant specific permissions directly to this user. These work in addition to role permissions.</small>
                    </div>
                </div>

                <!-- Roles Section (Only visible to superadmin) -->
                @if(auth()->user()->is_super_admin)
                <div class="form-section">
                    <h6 class="form-section-title">
                        <i class='bx bx-shield'></i>
                        Roles & Permissions
                    </h6>

                    <div class="alert alert-info mt-3">
                        <i class='bx bx-info-circle'></i>
                        <small>Change the user's role. Each role comes with specific permissions and access levels.</small>
                    </div>

                    <div class="roles-grid">
                        @foreach($roles as $role)
                            <div class="role-card" onclick="selectRole({{ $role->id }})">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="roles" value="{{ $role->id }}"
                                           id="role_{{ $role->id }}"
                                           {{ in_array($role->id, $userRoles) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ ucfirst($role->display_name ?? $role->name) }}
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    @if($role->name === 'admin')
                                        Full system access and management
                                    @elseif($role->name === 'staff')
                                        Standard staff permissions
                                    @elseif($role->name === 'technician')
                                        Equipment and maintenance access
                                    @else
                                        {{ $role->name }} permissions
                                    @endif
                                </small>
                            </div>
                        @endforeach
                    </div>

                    @error('roles')
                        <div class="text-danger mt-2" style="font-size: 0.875rem;">{{ $message }}</div>
                    @enderror

                </div>
                @endif

            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
                    <i class='bx bx-x'></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Update User
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Password strength checker
if (document.getElementById('password')) {
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('password-strength');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');

        if (password.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }

        strengthDiv.style.display = 'block';

        // Calculate strength
        let strength = 0;
        if (password.length >= 8) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[a-z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^A-Za-z0-9]/.test(password)) strength += 10;

        // Update UI
        strengthBar.className = 'strength-bar';
        if (strength < 30) {
            strengthBar.classList.add('strength-weak');
            strengthText.textContent = 'Password strength: Weak';
        } else if (strength < 60) {
            strengthBar.classList.add('strength-fair');
            strengthText.textContent = 'Password strength: Fair';
        } else if (strength < 90) {
            strengthBar.classList.add('strength-good');
            strengthText.textContent = 'Password strength: Good';
        } else {
            strengthBar.classList.add('strength-strong');
            strengthText.textContent = 'Password strength: Strong';
        }
    });
}

// Role and Permission Handler
document.addEventListener('DOMContentLoaded', function() {
    const rolePermissions = @json($roles->pluck('permissions', 'id'));

    function updatePermissions(roleId) {
        const permissions = rolePermissions[roleId] || [];
        const permissionIds = permissions.map(p => p.id);

        // Check permissions that belong to the selected role
        document.querySelectorAll('input[name="direct_permissions[]"]').forEach(function(checkbox) {
            if (permissionIds.includes(parseInt(checkbox.value))) {
                checkbox.checked = true;
            } else {
                // Optionally uncheck if not in role, but keep user's existing selections
                // For now, only check role permissions, don't uncheck others
            }
        });
    }

    // Listen for role selection changes
    document.querySelectorAll('input[name="roles"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                updatePermissions(this.value);
            }
        });
    });

    // Initialize on page load if a role is selected
    const selectedRole = document.querySelector('input[name="roles"]:checked');
    if (selectedRole) {
        updatePermissions(selectedRole.value);
    }
});

// Role selection handler (for UI feedback)
function selectRole(roleId) {
    const radio = document.getElementById('role_' + roleId);
    const card = radio.closest('.role-card');

    // Remove selected class from all cards
    document.querySelectorAll('.role-card').forEach(function(c) {
        c.classList.remove('selected');
    });

    // Add selected class to the chosen card
    card.classList.add('selected');

    // The radio button will handle the selection automatically
}

// Initialize role cards
document.addEventListener('DOMContentLoaded', function() {
    @foreach($roles as $role)
        const radio{{ $role->id }} = document.getElementById('role_{{ $role->id }}');
        if (radio{{ $role->id }} && radio{{ $role->id }}.checked) {
            radio{{ $role->id }}.closest('.role-card').classList.add('selected');
        }
    @endforeach
});
</script>
@endpush

@endsection