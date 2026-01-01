@extends('layouts.app')

@section('title', 'Add User')

@section('breadcrumbs')
    <a href="{{ route('accounts.index') }}">Accounts</a>
    <span class="separator">/</span>
    <span class="current">Add User</span>
@endsection

@section('page_title', 'Accounts Management')
@section('page_description', 'Manage all user accounts and permissions')


@push('styles')
    <link href="{{ asset('css/accounts/accounts.css') }}" rel="stylesheet">

@endpush

@section('content')
<div class="content">
    <div class="action-buttons">
      
        <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary" style="margin: 0 1rem">
            <i class='bx bx-arrow-back me-1'></i> Back to Users
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class='bx bx-error-circle'></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">


        <form action="{{ route('accounts.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf

            <div class="form-body">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <p class="text-sm text-gray-600 mb-0">Fill in the details to create a new user account</p>
                    <h6 class="form-section-title">
                        <i class='bx bx-user'></i>
                        Basic Information
                    </h6>

                    <div class="form-row">
                        <div class="form-group required">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*required</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                   id="first_name" name="first_name" value="{{ old('first_name') }}" required
                                   placeholder="Enter first name">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="form-group required">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*required</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name" value="{{ old('last_name') }}" required
                                   placeholder="Enter last name">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group required">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*required</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" required
                                   placeholder="Enter email address">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group required">
                            <label for="position" class="form-label">Position <span class="text-danger">*required</span></label>
                            <input type="text" class="form-control @error('position') is-invalid @enderror"
                                   id="position" name="position" value="{{ old('position') }}" required
                                   placeholder="Job title or position">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text">Enter the user's job title or position</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone') }}"
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
                        <div class="form-group required">
                            <label for="password" class="form-label">Password <span class="text-danger">*required</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required
                                   placeholder="Enter password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text">Minimum 8 characters</small>

                            <!-- Password Strength Indicator -->
                            <div class="password-strength" id="password-strength" style="display: none;">
                                <div class="strength-meter">
                                    <div class="strength-bar" id="strength-bar"></div>
                                </div>
                                <div class="strength-text" id="strength-text">Password strength: </div>
                            </div>
                        </div>

                        <div class="form-group required">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*required</span></label>
                            <input type="password" class="form-control"
                                   id="password_confirmation" name="password_confirmation" required
                                   placeholder="Confirm password">
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
                            <label for="office_id" class="form-label">Office <span class="text-danger">*required</span></label>
                            <select class="form-select @error('office_id') is-invalid @enderror"
                                    id="office_id" name="office_id" required>
                                <option value="">Select Office</option>
                                @foreach(\App\Models\Campus::where('is_active', true)->orderBy('name')->get() as $campus)
                                    <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                        @foreach($campus->offices->where('is_active', true) as $office)
                                            <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected' : '' }}>
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

                <!-- Roles Section -->
                <div class="form-section">
                    <h6 class="form-section-title">
                        <i class='bx bx-shield'></i>
                        Roles & Permissions
                    </h6>

                
                    <div class="alert alert-info mt-3">
                        <i class='bx bx-info-circle'></i>
                        <small>Select one or more roles for this user. Each role comes with specific permissions and access levels.</small>
                    </div>

                    <div class="roles-grid">
                        @foreach($roles as $role)
                            <div class="role-card" onclick="selectRole({{ $role->id }})">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="roles" value="{{ $role->id }}"
                                           id="role-{{ $role->id }}"
                                           @if(old('roles') == $role->id || (is_null(old('roles')) && $loop->first)) checked @endif required>
                                    <label class="form-check-label" for="role-{{ $role->id }}">
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

                <button type="reset" class="btn btn-outline-secondary">
                    <i class='bx bx-reset'></i> Reset Form
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Create User
                </button>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Password strength checker
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


// Initialize role cards
document.addEventListener('DOMContentLoaded', function() {
    updateRoleSelection();
});

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
    var radio = document.getElementById('role-' + roleId);
    if (!radio) {
        return;
    }

    radio.checked = true;
    updateRoleSelection();
}

// Form validation and SweetAlert integration
(function () {
    'use strict'

    var forms = document.querySelectorAll('.needs-validation')

    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
                
                // Show SweetAlert with missing fields
                const invalidFields = form.querySelectorAll(':invalid');
                let missingFields = [];
                
                invalidFields.forEach(field => {
                    const label = document.querySelector(`label[for="${field.id}"]`);
                    const fieldName = label ? label.textContent.replace('*', '').trim() : field.name;
                    missingFields.push(fieldName);
                });
                
                if (missingFields.length > 0) {
                    window.SweetAlert.warning(`Please fill in the following required fields:<br><br><strong>${missingFields.join('<br>')}</strong>`);
                }
                
                return;
            }

            // Show loading SweetAlert
            window.SweetAlert.loading('Creating User...', 'Please wait while we create the user account.');

            // Prevent default form submission to handle via AJAX
            event.preventDefault();
            
            // Submit form via AJAX
            var formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (response.status === 422) {
                    // Handle validation errors
                    return response.json().then(data => {
                        console.log('Validation errors:', data);
                        
                        // Show validation error message
                        let errorMessage = 'Please fix the following errors:\n';
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                errorMessage += `- ${data.errors[field][0]}\n`;
                            });
                        } else if (data.message) {
                            errorMessage = data.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: errorMessage,
                            confirmButtonColor: '#dc3545'
                        });
                        
                        // Close loading SweetAlert
                        window.SweetAlert.close();
                        
                        // Don't continue to success handler
                        throw new Error('Validation failed');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                // Close loading SweetAlert first
                window.SweetAlert.close();
                
                if (data.success) {
                    // Show success SweetAlert with redirect
                    window.SweetAlert.successWithRedirect(
                        data.message, 
                        data.redirect || '{{ route("accounts.index") }}', 
                        3000
                    );
                } else {
                    // Show error message
                    window.SweetAlert.error(data.message || 'An error occurred while creating the user.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                
                // Close loading SweetAlert
                window.SweetAlert.close();
                
                if (error.message !== 'Validation failed') { // Only show generic error if not a validation error
                    window.SweetAlert.error('An unexpected error occurred. Please try again.');
                }
            });

            form.classList.add('was-validated')
        }, false)
    })
})()
</script>
@endpush
