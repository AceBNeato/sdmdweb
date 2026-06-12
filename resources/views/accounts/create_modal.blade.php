<div class="card form-card mb-0">
    <div class="card-header form-header border-0 pb-0">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">Add New User</h5>
                <p class="text-sm text-gray-600 mb-0">Fill in the details to create a new user account</p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" aria-label="Close">
                <i class='bx bx-x'></i> Close
            </button>
        </div>
    </div>

    <form action="{{ route('admin.accounts.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf

        <div class="card-body form-body pt-3">
            <!-- Basic Information Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class='bx bx-user'></i>
                    Basic Information
                </h6>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="create_first_name">First Name <span class="text-danger">*required</span></label>
                        <input type="text" class="form-control"
                               id="create_first_name" name="first_name" value="" required
                               placeholder="Enter first name">
                    </div>
                    <div class="form-group required">
                        <label for="create_last_name">Last Name <span class="text-danger">*required</span></label>
                        <input type="text" class="form-control"
                               id="create_last_name" name="last_name" value="" required
                               placeholder="Enter last name">
                    </div>
                    <div class="form-group required">
                        <label for="create_email">Email Address <span class="text-danger">*required</span></label>
                        <input type="email" class="form-control"
                               id="create_email" name="email" value="" required
                               placeholder="Enter email address">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="create_position">Position <span class="text-danger">*required</span></label>
                        <input type="text" class="form-control"
                               id="create_position" name="position" value="" required
                               placeholder="Job title or position">
                        <small class="form-text">Enter the user's job title or position</small>
                    </div>

                    <div class="form-group">
                        <label for="create_phone">Phone Number</label>
                        <input type="tel" class="form-control"
                               id="create_phone" name="phone" value=""
                               pattern="[0-9]*" placeholder="09123456789">
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
                        <label for="create_password">Password <span class="text-danger">*required</span></label>
                        <input type="password" class="form-control"
                               id="create_password" name="password" required
                               placeholder="Enter password" autocomplete="new-password">
                        <small class="form-text">Minimum 8 characters</small>

                        <!-- Password Strength Indicator -->
                        <div class="password-strength" id="create-password-strength" style="display: none;">
                            <div class="strength-meter">
                                <div class="strength-bar" id="create-strength-bar"></div>
                            </div>
                            <div class="strength-text" id="create-strength-text">Password strength: </div>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label for="create_password_confirmation">Confirm Password <span class="text-danger">*required</span></label>
                        <input type="password" class="form-control"
                               id="create_password_confirmation" name="password_confirmation" required
                               placeholder="Confirm password" autocomplete="new-password">
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
                        <label for="create_office_id">Office <span class="text-danger">*required</span></label>
                        <select class="form-select" id="create_office_id" name="office_id" required>
                            <option value="">Select Office</option>
                            @foreach($campuses as $campus)
                                <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                    @foreach($campus->offices->where('is_active', true) as $office)
                                        <option value="{{ $office->id }}">
                                            {{ $office->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
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
                    <small>Select one role for this user. The role comes with specific permissions and access levels.</small>
                </div>

                <div class="roles-grid">
                    @foreach($roles as $role)
                        <div class="role-card" onclick="selectCreateRole({{ $role->id }})">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="roles" value="{{ $role->id }}"
                                       id="create_role_{{ $role->id }}"
                                       @if($loop->first) checked @endif required>
                                <label class="form-check-label" for="create_role_{{ $role->id }}">
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
            </div>

        </div>
        <div class="form-actions d-flex justify-content-end gap-2 border-top p-3">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class='bx bx-x'></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" onclick="handleUserCreate(event, this)">
                <i class='bx bx-save'></i> Create User
            </button>
        </div>
    </form>
</div>

<script>
function handleUserCreate(event, button) {
    event.preventDefault();
    
    const form = button.closest('form');
    
    // Trigger Native HTML5 validation
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        
        // Find missing required fields to display in SweetAlert
        const invalidFields = form.querySelectorAll(':invalid');
        let missingFields = [];
        
        invalidFields.forEach(field => {
            const label = form.querySelector(`label[for="${field.id}"]`);
            const fieldName = label ? label.textContent.replace('*required', '').trim() : field.placeholder || field.name;
            missingFields.push(fieldName);
        });
        
        if (missingFields.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Required Fields Missing',
                html: `Please fill in the following required fields:<br><br><strong>${missingFields.join('<br>')}</strong>`,
                confirmButtonColor: '#3085d6'
            });
        }
        return;
    }
    
    // Check password match
    const password = form.querySelector('#create_password').value;
    const confirmPassword = form.querySelector('#create_password_confirmation').value;
    if (password !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Password Mismatch',
            text: 'Password and Password Confirmation do not match.',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    // Show loading SweetAlert
    if (window.SweetAlert && window.SweetAlert.loading) {
        window.SweetAlert.loading('Creating User...', 'Please wait while we create the user account.');
    } else {
        Swal.fire({
            title: 'Creating User...',
            text: 'Please wait while we create the user account.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    const formData = new FormData(form);
    
    // Submit form via AJAX
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (response.status === 422) {
            return response.json().then(data => {
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
                throw new Error('Validation failed');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close loading SweetAlert first
            Swal.close();
            
            // Close modal first
            const modal = bootstrap.Modal.getInstance(document.querySelector('#createUserModal'));
            if (modal) modal.hide();
            
            // Show success and then reload
            if (window.SweetAlert && window.SweetAlert.successWithRedirect) {
                window.SweetAlert.successWithRedirect(data.message || 'User created successfully', window.location.href, 2000);
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'User created successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to create user',
                text: data.message || 'An error occurred.'
            });
        }
    })
    .catch(error => {
        if (error.message !== 'Validation failed') {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.'
            });
        }
    });
}
</script>

<script>
// Password strength checker for creation
const createPasswordField = document.getElementById('create_password');
if (createPasswordField) {
    createPasswordField.addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('create-password-strength');
        const strengthBar = document.getElementById('create-strength-bar');
        const strengthText = document.getElementById('create-strength-text');

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

// Initialize role selection for creation
updateCreateRoleSelection();

function updateCreateRoleSelection() {
    document.querySelectorAll('.roles-grid .role-card').forEach(function(card) {
        if (card.querySelector('input[id^="create_role_"]')) {
            card.classList.remove('selected');
        }
    });

    document.querySelectorAll('.roles-grid input[name="roles"]:checked').forEach(function(radio) {
        if (radio.id.startsWith('create_role_')) {
            var roleCard = radio.closest('.role-card');
            if (roleCard) {
                roleCard.classList.add('selected');
            }
        }
    });
}

function selectCreateRole(roleId) {
    var radio = document.getElementById('create_role_' + roleId);
    if (!radio) {
        return;
    }

    radio.checked = true;
    updateCreateRoleSelection();
}
</script>
