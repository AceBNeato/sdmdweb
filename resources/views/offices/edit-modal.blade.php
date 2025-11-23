<div class="text-center mb-4">
    <p class="text-muted">Update office information below</p>
</div>

<form id="officeEditForm" method="POST" action="{{ route('admin.offices.update', $office) }}" class="needs-validation" novalidate>
    @csrf
    @method('PUT')
    
    <!-- Basic Information Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class="bx bx-info-circle me-2"></i>Basic Information
        </h5>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="field-container">
                    <label for="edit_name" class="form-label required">Office Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-building"></i></span>
                        <input type="text" class="form-control" id="edit_name" name="name" value="{{ old('name', $office->name) }}" required minlength="3" maxlength="255" placeholder="e.g., SDMD Office">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-container">
                    <label for="edit_campus_id" class="form-label required">Campus</label>
                    <select class="form-select" id="edit_campus_id" name="campus_id" required>
                        <option value="" disabled>Select Campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id', $office->campus_id) == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }} ({{ $campus->code }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Select the campus where this office is located</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class="bx bx-phone me-2"></i>Contact Information
        </h5>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="field-container">
                    <label for="edit_contact_number" class="form-label">Contact Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                        <input type="text" class="form-control" id="edit_contact_number" name="contact_number" value="{{ old('contact_number', $office->contact_number) }}" maxlength="20" placeholder="e.g., +63 123 456 7890">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-container">
                    <label for="edit_email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                        <input type="email" class="form-control" id="edit_email" name="email" value="{{ old('email', $office->email) }}" maxlength="255" placeholder="office@example.com">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Details Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class="bx bx-map me-2"></i>Location Details
        </h5>

        <div class="row g-3">
            <div class="col-12">
                <div class="field-container">
                    <label for="edit_location" class="form-label">Location</label>
                    <textarea class="form-control" id="edit_location" name="location" rows="3" maxlength="500" placeholder="Enter complete office location...">{{ old('location', $office->location) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class="bx bx-cog me-2"></i>Status Settings
        </h5>

        <div class="row g-3">
            <div class="col-12">
                <div class="field-container">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1" {{ old('is_active', $office->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                    <small class="text-muted">Toggle to set the office as active or inactive</small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <div>
            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                <i class='bx bx-x me-1'></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-save me-1'></i> Update Office
            </button>
        </div>
    </div>
</form>

<script>
// Handle edit form submission
$('#officeEditForm').on('submit', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var submitBtn = form.find('button[type="submit"]');
    var originalText = submitBtn.html();
    
    // Show loading state
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');
    
    // Submit via AJAX
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            // Check if response is JSON or redirect
            if (typeof response === 'object' && response.success) {
                // Show success SweetAlert
                Swal.fire({
                    icon: 'success',
                    title: 'Office Updated!',
                    text: response.message || 'Office has been updated successfully.',
                    confirmButtonText: 'Great!',
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true
                }).then(() => {
                    $('#officeEditModal').modal('hide');
                    window.location.reload();
                });
            } else {
                // Redirect response means success
                Swal.fire({
                    icon: 'success',
                    title: 'Office Updated!',
                    text: 'Office has been updated successfully.',
                    confirmButtonText: 'Great!',
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true
                }).then(() => {
                    $('#officeEditModal').modal('hide');
                    window.location.reload();
                });
            }
        },
        error: function(xhr) {
            // Handle validation errors
            if (xhr.status === 422 && xhr.responseJSON) {
                var errors = xhr.responseJSON.errors;
                var errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';
                for (var field in errors) {
                    errorHtml += '<li>' + errors[field][0] + '</li>';
                }
                errorHtml += '</ul></div>';
                
                // Show errors at the top of the modal
                form.find('.modal-body').prepend(errorHtml);
                
                // Remove error message after 5 seconds
                setTimeout(function() {
                    form.find('.alert').fadeOut();
                }, 5000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was an error updating the office. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
            }
        },
        complete: function() {
            // Restore button state
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
});
</script>
