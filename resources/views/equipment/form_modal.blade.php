
@php
    if (auth()->guard('technician')->check()) {
        $prefix = 'technician';
    } elseif (auth()->guard('staff')->check()) {
        $prefix = 'staff';
    } else {
        $prefix = 'admin';
    }
@endphp

<div class="text-center mb-4">
    <p class="text-muted">Fill in the details below to {{ $equipment->exists ? 'update' : 'create' }} equipment for your office</p>
</div>

<form action="{{ $equipment->exists ? route($prefix . '.equipment.update', $equipment) : route($prefix . '.equipment.store') }}" method="POST" class="needs-validation" novalidate>
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
            <div class="col-md-4">
                <div class="field-container">
                    <label for="brand" class="form-label required">Brand</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-tag'></i></span>
                        <input type="text" class="form-control @error('brand') is-invalid @enderror"
                               id="brand" name="brand"
                               value="{{ old('brand', $equipment->brand ?? '') }}" required
                               placeholder="e.g., Epson">
                        @error('brand')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="field-container">
                    <label for="model_number" class="form-label required">Model</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-chip'></i></span>
                        <input type="text" class="form-control @error('model_number') is-invalid @enderror"
                               id="model_number" name="model_number"
                               value="{{ old('model_number', $equipment->model_number ?? '') }}" required
                               placeholder="e.g., L3110">
                        @error('model_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="field-container">
                    <label for="equipment_model_display" class="form-label">Equipment Model</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-show'></i></span>
                        <input type="text" class="form-control" id="equipment_model_display"
                               value="{{ old('brand', $equipment->brand ?? '') . old('model_number', $equipment->model_number ?? '') }}"
                               readonly placeholder="Auto-generated">
                    </div>
                    <small class="text-muted">Brand + Model (auto-generated)</small>
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

@push('scripts')
<script>
// Update equipment model display when brand or model changes
document.addEventListener('DOMContentLoaded', function() {
    const brandInput = document.getElementById('brand');
    const modelInput = document.getElementById('model_number');
    const equipmentModelDisplay = document.getElementById('equipment_model_display');

    function updateEquipmentModelDisplay() {
        const brand = brandInput.value.trim();
        const model = modelInput.value.trim();
        const concatenated = brand + model;
        equipmentModelDisplay.value = concatenated;
    }

    // Update on input change
    brandInput.addEventListener('input', updateEquipmentModelDisplay);
    modelInput.addEventListener('input', updateEquipmentModelDisplay);

    // Initial update
    updateEquipmentModelDisplay();
});
</script>
@endpush
