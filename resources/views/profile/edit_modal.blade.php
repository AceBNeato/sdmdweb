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
            <input type="password" name="current_password" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="new_password_confirmation" class="form-control">
        </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">
            <i class='bx bx-save me-1'></i>Save Changes
        </button>
    </div>
</form>

<script>
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
})();
</script>
