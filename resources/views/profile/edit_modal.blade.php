@php
    $user = $user ?? auth('staff')->user() ?? auth('technician')->user() ?? auth()->user();
    $routePrefix = auth('technician')->check() ? 'technician' : (auth('staff')->check() ? 'staff' : 'admin');
    $updateRoute = $routePrefix === 'staff' ? route('staff.profile.update') : ($routePrefix === 'technician' && Route::has('technician.profile.update') ? route('technician.profile.update') : ($routePrefix === 'admin' ? route('admin.profile.update') : '#'));
@endphp

<form action="{{ $updateRoute }}" method="POST" enctype="multipart/form-data" id="profileEditForm" class="profile-edit-form">
    @csrf
    @method('PUT')

    <div class="mb-3 text-center">
        <div class="profile-avatar-wrapper">
            <img src="{{ ($user->profile_photo ?? $user->profile_photo_path) ? asset('storage/' . ($user->profile_photo ?? $user->profile_photo_path)) : asset('images/SDMDlogo.png') }}"
                 class="profile-avatar profile-avatar-md"
                 id="profileImagePreview"
                 onerror="this.onerror=null; this.src='{{ asset('images/SDMDlogo.png') }}'">
            <button type="button" class="btn btn-sm btn-primary profile-avatar-upload-btn" onclick="document.getElementById('profileImageInput').click()">
                <i class='bx bx-upload'></i>
            </button>
        </div>
        <input type="file" id="profileImageInput" name="profile_photo" class="d-none" accept="image/*">
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $user->address) }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Employee ID</label>
            <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $user->employee_id) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Specialization</label>
            <input type="text" name="specialization" class="form-control" value="{{ old('specialization', $user->specialization) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Skills</label>
            <textarea name="skills" rows="3" class="form-control">{{ old('skills', $user->skills) }}</textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">Current Password</label>
            <div class="input-group">
                <input type="password" name="current_password" class="form-control" id="currentPassword">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                    <i class="bx bx-show" id="currentPasswordIcon"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" name="new_password" class="form-control" id="newPassword">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                    <i class="bx bx-show" id="newPasswordIcon"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirm New Password</label>
            <div class="input-group">
                <input type="password" name="new_password_confirmation" class="form-control" id="confirmNewPassword">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmNewPassword')">
                    <i class="bx bx-show" id="confirmNewPasswordIcon"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">
            <i class='bx bx-save me-1'></i>Save Changes
        </button>
    </div>
</form>

<script>
// Check if SweetAlert is available, if not add it
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}

// Toggle password visibility function
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + 'Icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.className = 'bx bx-hide';
    } else {
        passwordField.type = 'password';
        icon.className = 'bx bx-show';
    }
}

(function(){
    const input = document.getElementById('profileImageInput');
    const preview = document.getElementById('profileImagePreview');
    if (input && preview) {
        input.addEventListener('change', function(e){
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(evt){ preview.src = evt.target.result; };
            reader.readAsDataURL(file);
        });
    }

    // Handle form submission with SweetAlert
    const form = document.getElementById('profileEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...';
            
            // Create FormData for file upload
            const formData = new FormData(form);
            
            // Submit via fetch
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value
                }
            })
            .then(response => {
                // Check if response is JSON or redirect
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => ({ type: 'json', data }));
                } else {
                    // It's a redirect response, treat as success
                    return response.text().then(html => ({ type: 'redirect', html }));
                }
            })
            .then(result => {
                if (result.type === 'json') {
                    const data = result.data;
                    if (data.success) {
                        // Show success SweetAlert
                        Swal.fire({
                            icon: 'success',
                            title: 'Profile Updated!',
                            text: data.message || 'Your profile has been updated successfully.',
                            confirmButtonText: 'Great!',
                            confirmButtonColor: '#28a745',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: true
                        }).then(() => {
                            // Close modal and reload page to show updated profile
                            const modal = form.closest('.modal');
                            if (modal) {
                                const bsModal = bootstrap.Modal.getInstance(modal);
                                if (bsModal) bsModal.hide();
                            }
                            window.location.reload();
                        });
                    } else {
                        // Show error SweetAlert
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: data.message || 'There was an error updating your profile. Please try again.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } else if (result.type === 'redirect') {
                    // Redirect response means success
                    Swal.fire({
                        icon: 'success',
                        title: 'Profile Updated!',
                        text: 'Your profile has been updated successfully.',
                        confirmButtonText: 'Great!',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: true
                    }).then(() => {
                        // Close modal and reload page
                        const modal = form.closest('.modal');
                        if (modal) {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }
                        window.location.reload();
                    });
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                // Show error SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'There was an error updating your profile. Please try again.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
})();
</script>
