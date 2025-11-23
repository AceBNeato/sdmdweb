<div class="office-details">
    <div class="text-center mb-4">
        <p class="text-muted">Office location and contact information</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="field-container">
                <label class="form-label text-muted">Office Name</label>
                <div class="fw-semibold">{{ $office->name }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="field-container">
                <label class="form-label text-muted">Campus</label>
                <div class="fw-semibold">{{ $office->campus?->name ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-12">
            <div class="field-container">
                <label class="form-label text-muted">Location</label>
                <div class="fw-semibold">{{ $office->location ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="field-container">
                <label class="form-label text-muted">Contact Number</label>
                <div class="fw-semibold">
                    @if($office->contact_number)
                        <a href="tel:{{ $office->contact_number }}" class="text-decoration-none">{{ $office->contact_number }}</a>
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="field-container">
                <label class="form-label text-muted">Email</label>
                <div class="fw-semibold">
                    @if($office->email)
                        <a href="mailto:{{ $office->email }}" class="text-decoration-none">{{ $office->email }}</a>
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="field-container">
                <label class="form-label text-muted">Status</label>
                <div class="fw-semibold">
                    @if($office->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="field-container">
                <label class="form-label text-muted">Created At</label>
                <div class="fw-semibold">{{ $office->created_at->format('M d, Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>
