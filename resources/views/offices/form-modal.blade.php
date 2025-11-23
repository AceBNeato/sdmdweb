<div class="text-center mb-4">
    <p class="text-muted">Fill in the details below to create a new office location</p>
</div>

<form id="officeCreateForm" method="POST" action="{{ route('admin.offices.store') }}" class="needs-validation" novalidate>
    @csrf
    
    <!-- Basic Information Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class="bx bx-info-circle me-2"></i>Basic Information
        </h5>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="field-container">
                    <label for="create_name" class="form-label required">Office Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-building"></i></span>
                        <input type="text" class="form-control" id="create_name" name="name" required minlength="3" maxlength="255" placeholder="e.g., SDMD Office">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-container">
                    <label for="create_campus_id" class="form-label required">Campus</label>
                    <select class="form-select" id="create_campus_id" name="campus_id" required>
                        <option value="" disabled selected>Select Campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}">{{ $campus->name }} ({{ $campus->code }})</option>
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
                    <label for="create_contact_number" class="form-label">Contact Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                        <input type="text" class="form-control" id="create_contact_number" name="contact_number" maxlength="20" placeholder="e.g., +63 123 456 7890">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field-container">
                    <label for="create_email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                        <input type="email" class="form-control" id="create_email" name="email" maxlength="255" placeholder="office@example.com">
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
                    <label for="create_location" class="form-label">Location/Area</label>
                    <textarea class="form-control" id="create_location" name="location" rows="3" maxlength="500" placeholder="Enter complete office location..."></textarea>
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
                        <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="create_is_active">Active</label>
                    </div>
                    <small class="text-muted">Office will be visible and functional when active</small>
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
                <i class='bx bx-save me-1'></i> Create Office
            </button>
        </div>
    </div>
</form>
