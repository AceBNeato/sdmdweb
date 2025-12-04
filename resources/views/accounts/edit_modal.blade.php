@php
    $user = $user ?? (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user());
@endphp

<div class="card form-card mb-0">
    <div class="card-header form-header border-0 pb-0">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">Edit User Information</h5>
                <p class="text-sm text-gray-600 mb-0">Update user details, password, and roles</p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" aria-label="Close">
                <i class='bx bx-x'></i> Close
            </button>
        </div>
    </div>

    <form action="{{ route('admin.accounts.update', $user) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="card-body form-body pt-3">
            <!-- Basic Information Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class='bx bx-user'></i>
                    Basic Information
                </h6>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                               placeholder="Enter first name">
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group required">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                               placeholder="Enter last name">
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group required">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email', $user->email) }}" required
                               placeholder="Enter email address">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
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
                               id="password" name="password" autocomplete="new-password"
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
                               id="password_confirmation" name="password_confirmation" autocomplete="new-password"
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
 <!-- Roles Section (Only visible to superadmin and admin) -->
            @if(auth()->user()->is_super_admin || auth()->user()->is_admin)
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
        <div class="form-actions d-flex justify-content-end gap-2 border-top p-3">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class='bx bx-x'></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" onclick="handleUserUpdate(event, this)">
                <i class='bx bx-save'></i> Update User
            </button>
        </div>
    </form>
</div>

<script>
function handleUserUpdate(event, button) {
    event.preventDefault();
    
    const form = button.closest('form');
    const formData = new FormData(form);
    const currentUserId = @json(auth()->check() ? auth()->id() : null);
    const updatingUserId = @json($user->id);
    
    // Check if role is being changed for current user
    const isCurrentUserRoleChange = currentUserId === updatingUserId;
    
    // Submit form via AJAX
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aggressively clear ALL session messages immediately
            if (window.sessionMessages) {
                window.sessionMessages = {
                    success: null,
                    error: null,
                    warning: null,
                    info: null
                };
            }
            
            // Also clear any pending SweetAlert
            if (window.SweetAlert) {
                window.SweetAlert.close();
            }
            
            // Show success message with redirect
            if (isCurrentUserRoleChange && data.logout_required) {
                window.SweetAlert.successWithRedirect(data.message || 'User updated successfully', data.redirect_url || '/login', 2000);
            } else {
                // Close modal first
                const modal = bootstrap.Modal.getInstance(document.querySelector('#editUserModal'));
                if (modal) modal.hide();
                
                // Show success and then reload
                window.SweetAlert.successWithRedirect(data.message || 'User updated successfully', window.location.href, 2000);
            }
        } else {
            window.SweetAlert.error(data.message || 'Failed to update user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Fallback to regular form submission
        form.submit();
    });
}
</script>

<script>
// Role permissions data
const rolePermissions = {
    @foreach($roles as $role)
        '{{ $role->id }}': [{{ $role->permissions->pluck('id')->join(',') }}],
    @endforeach
};

// Password strength checker
const passwordField = document.getElementById('password');
if (passwordField) {
    passwordField.addEventListener('input', function() {
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

// Initialize role cards
updateRoleSelection();

// Clear manual overrides on initialization
resetManualOverrides();

// Initialize direct permissions based on selected role
const initialSelectedRole = document.querySelector('.roles-grid input[name="roles"]:checked');
if (initialSelectedRole) {
    updateDirectPermissionsForRole(initialSelectedRole.value);
}

function updateRoleSelection() {
    document.querySelectorAll('.roles-grid .role-card').forEach(function(card) {
        card.classList.remove('selected');
    });

    document.querySelectorAll('.roles-grid input[name="roles"]:checked').forEach(function(radio) {
        var roleCard = radio.closest('.role-card');
        if (roleCard) {
            roleCard.classList.add('selected');
        }
    });
}

function selectRole(roleId) {
    var radio = document.getElementById('role_' + roleId);
    if (!radio) {
        return;
    }

    radio.checked = true;
    updateRoleSelection();

    // Clear manual override flags when role changes so new role permissions can be suggested
    resetManualOverrides();

    // Update direct permissions based on selected role
    updateDirectPermissionsForRole(roleId);
}

function resetManualOverrides() {
    // Clear all manual override flags when role changes
    document.querySelectorAll('.permissions-grid input[name="direct_permissions[]"]').forEach(function(checkbox) {
        checkbox.removeAttribute('data-manually-unchecked');
    });
}

function updateDirectPermissionsForRole(roleId) {
    // Get the permissions for this role
    const rolePerms = rolePermissions[roleId] || [];

    // Reset all direct permissions to unchecked first
    document.querySelectorAll('.permissions-grid input[name="direct_permissions[]"]').forEach(function(checkbox) {
        checkbox.checked = false;
        checkbox.removeAttribute('data-manually-unchecked');
        checkbox.removeAttribute('data-role-suggested');
    });

    // Check the permissions that belong to this role
    rolePerms.forEach(function(permId) {
        const checkbox = document.getElementById('permission_' + permId);
        if (checkbox) {
            checkbox.checked = true;
            checkbox.setAttribute('data-role-suggested', 'true');
        }
    });
}

// Track manual permission changes
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to permission checkboxes to track manual changes
    document.querySelectorAll('.permissions-grid input[name="direct_permissions[]"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                // Mark as manually unchecked
                this.setAttribute('data-manually-unchecked', 'true');
            } else {
                // Remove manual override when checked
                this.removeAttribute('data-manually-unchecked');
            }
        });
    });
});
</script>
