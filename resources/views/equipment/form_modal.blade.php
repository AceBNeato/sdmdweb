@php
    $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');
@endphp

@push('styles')
    <style>
        /* Form styling */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }

        /* Focus state - Blue highlight */
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
            background-color: #f8f9ff;
        }

        /* Success state - Green highlight */
        .form-control.is-valid, .form-select.is-valid {
            border-color: #28a745;
            background-color: #f8fff9;
        }

        .form-control.is-valid:focus, .form-select.is-valid:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
        }

        /* Error state - Red highlight */
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
            background-color: #fff5f5;
        }

        .form-control.is-invalid:focus, .form-select.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        }

        /* Input group icons */
        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }

        .form-control:focus + .input-group-text {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
        }

        /* Field containers */
        .field-container {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .field-container:hover {
            border-color: #dee2e6;
        }

        .field-container:focus-within {
            border-color: #007bff;
            box-shadow: 0 0 0 0.1rem rgba(0, 123, 255, 0.1);
        }

        /* Form sections */
        .form-section {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
    </style>
@endpush

<div class="text-center mb-4">
    <p class="text-muted">Fill in the details below to {{ $equipment->exists ? 'update' : 'create' }} equipment for your office</p>
</div>

<form action="{{ $equipment->exists ? route((auth()->guard('technician')->check() ? 'technician.' : 'admin.') . 'equipment.update', $equipment) : route((auth()->guard('technician')->check() ? 'technician.' : 'admin.') . 'equipment.store') }}" method="POST" class="needs-validation" novalidate>
    @csrf
    @if($equipment->exists)
        @method('PUT')
    @endif

    <!-- Basic Information Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class='bx bx-info-circle me-2'></i>Basic Information
        </h5>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="field-container">
                    <label for="model_number" class="form-label required">Equipment Model</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-tag'></i></span>
                        <input type="text" class="form-control @error('model_number') is-invalid @enderror"
                               id="model_number" name="model_number"
                               value="{{ old('model_number', $equipment->model_number ?? '') }}" required>
                        @error('model_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="field-container">
                    <label for="serial_number" class="form-label required">Serial Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-barcode'></i></span>
                        <input type="text" class="form-control @error('serial_number') is-invalid @enderror"
                               id="serial_number" name="serial_number"
                               value="{{ old('serial_number', $equipment->serial_number ?? '') }}" required>
                        @error('serial_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                 <div class="field-container">
                    <label for="office_id" class="form-label required">Office</label>


                    @if(auth()->user() && auth()->user()->is_staff)
                {{-- Staff users can only create/edit equipment in their own office --}}
                <input type="hidden" name="office_id" value="{{ auth()->user()->office_id }}">
                <input type="text" class="form-control" value="{{ auth()->user()->office->name ?? 'Your Office' }}" readonly disabled>
                <small class="text-muted">Equipment will be assigned to your office</small>
            @else
                {{-- Admin, Super Admin, and Technician can select/change office --}}
                <select class="form-select @error('office_id') is-invalid @enderror"
                        id="office_id" name="office_id" required>
                    <option value="" disabled {{ !old('office_id', $equipment->office_id) ? 'selected' : '' }}>Select Office</option>
                    @if(isset($campuses) && $campuses->count() > 0)
                        @foreach($campuses as $campus)
                            @if($campus->offices->where('is_active', true)->count() > 0)
                                <optgroup label="{{ $campus->name }}">
                                    @foreach($campus->offices->where('is_active', true) as $office)
                                        <option value="{{ $office->id }}" {{ old('office_id', $equipment->office_id) == $office->id ? 'selected' : '' }}>
                                            {{ $office->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    @else
                        <option value="" disabled>No offices available</option>
                    @endif
                </select>
                <small class="text-muted">{{ $equipment->exists ? 'Change the office where this equipment is located' : 'Select the office where this equipment will be located' }}</small>
            @endif
                    @error('office_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Details Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class='bx bx-cog me-2'></i>Equipment Details
        </h5>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="field-container">
                    <label for="equipment_type_id" class="form-label required">Equipment Type</label>
                    <select class="form-select @error('equipment_type_id') is-invalid @enderror"
                            id="equipment_type_id" name="equipment_type_id" required>
                        <option value="" disabled {{ !old('equipment_type_id', $equipment->equipment_type_id) ? 'selected' : '' }}>Select Type</option>
                        @foreach($equipmentTypes as $id => $name)
                            <option value="{{ $id }}" {{ old('equipment_type_id', $equipment->equipment_type_id) == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('equipment_type_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="field-container">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select @error('category_id') is-invalid @enderror"
                            id="category_id" name="category_id">
                        <option value="" {{ !old('category_id', $equipment->category_id ?? '') ? 'selected' : '' }}>Select Category</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" {{ old('category_id', $equipment->category_id ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="field-container">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-calendar'></i></span>
                        <input type="date" class="form-control @error('purchase_date') is-invalid @enderror"
                               id="purchase_date" name="purchase_date"
                               value="{{ old('purchase_date', $equipment->purchase_date ? $equipment->purchase_date->format('Y-m-d') : '') }}">
                        @error('purchase_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="field-container">
                    <label for="cost_of_purchase" class="form-label">Cost of Purchase</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-dollar'></i></span>
                        <input type="number" class="form-control @error('cost_of_purchase') is-invalid @enderror"
                               id="cost_of_purchase" name="cost_of_purchase" step="0.01" min="0"
                               value="{{ old('cost_of_purchase', $equipment->cost_of_purchase ?? '') }}"
                               placeholder="0.00">
                        @error('cost_of_purchase')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="field-container">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description" rows="3"
                              placeholder="Enter equipment description...">{{ old('description', $equipment->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Status & Condition Section -->
    <div class="form-section">
        <h5 class="form-section-title">
            <i class='bx bx-check-circle me-2'></i>Status & Condition
        </h5>

        <div class="row g-3">
            @if($equipment->exists)
            <!-- Show status and condition fields only when editing existing equipment -->
            <div class="col-md-6">
                <div class="field-container">
                    <label for="status" class="form-label required">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror"
                            id="status" name="status" required>
                        <option value="" disabled {{ !old('status', $equipment->status ?? '') ? 'selected' : '' }}>Select Status</option>
                        <option value="serviceable" {{ old('status', $equipment->status ?? '') == 'serviceable' ? 'selected' : '' }}>Serviceable</option>
                        <option value="for_repair" {{ old('status', $equipment->status ?? '') == 'for_repair' ? 'selected' : '' }}>For Repair</option>
                        <option value="defective" {{ old('status', $equipment->status ?? '') == 'defective' ? 'selected' : '' }}>Defective</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="field-container">
                    <label for="condition_display" class="form-label">Condition</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-shield-check'></i></span>
                        <input type="text" class="form-control" id="condition_display"
                               value="Select a status first" readonly>
                        <input type="hidden" id="condition" name="condition" value="">
                    </div>
                    <div class="form-text">Condition is automatically set based on equipment status</div>
                </div>
            </div>
            @else
            <!-- For new equipment, show informational message -->
            <div class="col-12">
                <div class="alert alert-info border-0 mb-0">
                    <i class='bx bx-info-circle me-2'></i>
                    <strong>New Equipment:</strong> Status will be set to <strong>"Serviceable"</strong> and Condition to <strong>"Good"</strong> automatically.
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <div>
            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                <i class='bx bx-x me-1'></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-save me-1'></i> {{ $equipment->exists ? 'Update' : 'Create' }} Equipment
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Only run this script when editing existing equipment (status field is visible)
    @if($equipment->exists)
    const statusSelect = document.getElementById('status');
    const conditionDisplay = document.getElementById('condition_display');
    const conditionHidden = document.getElementById('condition');

    // Function to update condition based on status
    function updateCondition() {
        const selectedStatus = statusSelect.value;

        if (!selectedStatus) {
            conditionDisplay.value = 'Select a status first';
            conditionHidden.value = '';
            return;
        }

        // Set condition based on status
        let conditionValue = '';
        let conditionText = '';

        switch(selectedStatus) {
            case 'serviceable':
                conditionValue = 'good';
                conditionText = 'Good';
                break;
            case 'for_repair':
            case 'defective':
                conditionValue = 'not_working';
                conditionText = 'Not Working';
                break;
            default:
                conditionValue = '';
                conditionText = 'Unknown';
        }

        conditionDisplay.value = conditionText;
        conditionHidden.value = conditionValue;
    }

    // Update condition when status changes
    statusSelect.addEventListener('change', updateCondition);

    // Set initial condition for existing equipment or old input
    updateCondition();
    @endif
});
</script>
