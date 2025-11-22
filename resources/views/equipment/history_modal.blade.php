@php
    $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->role?->name === 'technician' ? 'technician' : 'staff');
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
    <p class="text-muted">Record maintenance or service activity for {{ $equipment->model_number }} (SN: {{ $equipment->serial_number }})</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route($prefix . '.equipment.history.store', $equipment) }}" method="POST">
    @csrf

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="date" class="form-label">Date & Time <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control @error('date') is-invalid @enderror"
                       id="date" name="date" value="{{ now()->format('Y-m-d\TH:i') }}" readonly required>
                <div class="form-text">Date and time are automatically set when the history entry is created</div>
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="jo_number" class="form-label">Job Order Number <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control @error('jo_number') is-invalid @enderror"
                           id="jo_number_display" name="jo_number_display"
                           value="{{ old('jo_number_display') }}"
                           placeholder="JO-YY-MM-XXX" readonly>
                    <input type="hidden" id="jo_number" name="jo_number" value="{{ old('jo_number') }}">
                </div>
                <div class="form-text">Job Order Number is auto-generated based on the selected date</div>
                @error('jo_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label for="action_taken" class="form-label">Action Taken <span class="text-danger">*</span></label>
        <textarea class="form-control @error('action_taken') is-invalid @enderror"
                  id="action_taken" name="action_taken" rows="4"
                  placeholder="Describe the maintenance or service action performed..." required>{{ old('action_taken') }}</textarea>
        @error('action_taken')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>



    <div class="mb-3">
        <div class="field-container">
            <label for="equipment_status" class="form-label required">Equipment Status</label>
            <select class="form-select @error('equipment_status') is-invalid @enderror"
                    id="equipment_status" name="equipment_status" required>
                <option value="" disabled {{ !old('equipment_status', $equipment->status ?? '') ? 'selected' : '' }}>Select Status</option>
                <option value="serviceable" {{ old('equipment_status', $equipment->status ?? '') == 'serviceable' ? 'selected' : '' }}>Serviceable</option>
                <option value="for_repair" {{ old('equipment_status', $equipment->status ?? '') == 'for_repair' ? 'selected' : '' }}>For Repair</option>
                <option value="defective" {{ old('equipment_status', $equipment->status ?? '') == 'defective' ? 'selected' : '' }}>Defective</option>
            </select>
            <div class="form-text">Setting status to Serviceable will also set condition to Good</div>
            @error('equipment_status')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea class="form-control @error('remarks') is-invalid @enderror"
                  id="remarks" name="remarks" rows="3"
                  placeholder="Remarks will be auto-generated based on equipment status" readonly>{{ old('remarks') }}</textarea>
        <div class="form-text">Remarks are automatically generated based on the selected equipment status</div>
        @error('remarks')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex gap-3">
        <button type="submit" class="btn btn-primary">
            <i class='bx bx-save me-1'></i> Save History Entry
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class='bx bx-x me-1'></i> Cancel
        </button>
    </div>
</form>

<script>
// Initialize immediately since this is loaded via AJAX into modal
(function() {
    const dateInput = document.getElementById('date');
    const joNumberDisplay = document.getElementById('jo_number_display');
    const joNumberHidden = document.getElementById('jo_number');
    const statusSelect = document.getElementById('equipment_status');
    const remarksTextarea = document.getElementById('remarks');

    // Function to update remarks based on status
    function updateRemarks() {
        const selectedStatus = statusSelect.value;
        let remarksText = '';

        switch(selectedStatus) {
            case 'serviceable':
                remarksText = 'Serviceable';
                break;
            case 'for_repair':
                remarksText = 'For Repair';
                break;
            case 'defective':
                remarksText = 'Defective';
                break;
            default:
                remarksText = '';
        }

        remarksTextarea.value = remarksText;
    }

    // Auto-generate JO number when page loads (date is fixed to current time)
    function generateJONumber() {
        const selectedDate = dateInput.value;
        if (!selectedDate) {
            joNumberDisplay.value = '';
            joNumberHidden.value = '';
            return;
        }

        console.log('Generating JO number for date:', selectedDate.split('T')[0]);

        fetch('{{ route($prefix . ".equipment.generate-jo", $equipment) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ date: selectedDate.split('T')[0] })
        })
        .then(response => response.json())
        .then(data => {
            console.log('JO generation response:', data);
            if (data.success) {
                joNumberDisplay.value = data.jo_number;
                joNumberHidden.value = data.jo_number;
            } else {
                console.error('Failed to generate JO number:', data.message);
                joNumberDisplay.value = 'Error generating JO number';
                joNumberHidden.value = '';
            }
        })
        .catch(error => {
            console.error('Error generating JO number:', error);
            joNumberDisplay.value = 'Error generating JO number';
            joNumberHidden.value = '';
        });
    }

    // Generate JO number immediately since date is fixed
    generateJONumber();

    // Update remarks when status changes
    statusSelect.addEventListener('change', updateRemarks);

    // Initialize remarks based on current status
    updateRemarks();

    // Handle old input for JO number field
    const oldJONumber = "{{ old('jo_number') }}";
    if (oldJONumber) {
        joNumberDisplay.value = oldJONumber;
        joNumberHidden.value = oldJONumber;
    }

    // Handle old input for remarks
    const oldRemarks = "{{ old('remarks') }}";
    if (oldRemarks) {
        remarksTextarea.value = oldRemarks;
    }

})();
</script>
