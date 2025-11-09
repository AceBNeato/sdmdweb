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
                       id="date" name="date" value="{{ old('date', now()->format('Y-m-d\TH:i')) }}" required>
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="jo_number" class="form-label">Job Order Number <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="jo_prefix" readonly style="max-width: 150px;">
                    <input type="text" class="form-control @error('jo_number') is-invalid @enderror"
                           id="jo_sequence" name="jo_sequence"
                           value="{{ old('jo_sequence') }}"
                           placeholder="01" maxlength="2" pattern="[0-9]{1,2}" required>
                </div>
                <div class="form-text">Date prefix is auto-generated, enter sequence number (01, 02, etc.)</div>
                <input type="hidden" id="jo_number" name="jo_number" value="{{ old('jo_number') }}">
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
        <label for="remarks" class="form-label">Remarks</label>
        <textarea class="form-control @error('remarks') is-invalid @enderror"
                  id="remarks" name="remarks" rows="3"
                  placeholder="Additional notes or observations...">{{ old('remarks') }}</textarea>
        @error('remarks')
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
    const joPrefixInput = document.getElementById('jo_prefix');
    const joSequenceInput = document.getElementById('jo_sequence');
    const joNumberInput = document.getElementById('jo_number');

    let justAlerted = false;
    let justAlertedDate = false;

    // Update JO prefix when date changes
    function updateJOPrefix() {
        const selectedDate = dateInput.value;
        if (selectedDate) {
            const datePart = selectedDate.split('T')[0]; // Get YYYY-MM-DD
            const formattedDate = datePart.replace(/-/g, '-'); // Keep YYYY-MM-DD format
            joPrefixInput.value = `JO-${formattedDate}-`;
            updateFullJONumber();
        }
    }

    // Update the full JO number when sequence changes
    function updateFullJONumber() {
        const prefix = joPrefixInput.value;
        const sequence = joSequenceInput.value.padStart(2, '0'); // Ensure 2 digits
        if (prefix && sequence) {
            joNumberInput.value = `${prefix}${sequence}`;
        }
    }

    // Auto-update prefix when date changes
    dateInput.addEventListener('change', updateJOPrefix);

    // Update full JO number when sequence changes
    joSequenceInput.addEventListener('input', function() {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');
        updateFullJONumber();
        // Reset alert flag when user starts typing
        justAlerted = false;
    });

    // Validate consecutive sequence when sequence loses focus (real-time like date validation)
    joSequenceInput.addEventListener('blur', function() {
        // Skip validation if we just showed an alert
        if (justAlerted) return;

        const sequence = joSequenceInput.value.trim();
        const date = dateInput.value;

        // Only validate if we have both date and sequence
        if (!date || !sequence) return;

        // Convert to number and check if it's valid
        const sequenceNum = parseInt(sequence);
        if (isNaN(sequenceNum) || sequenceNum < 1) return;

        // Skip validation for sequence 1 (always allowed)
        if (sequenceNum === 1) return;

        console.log('Validating sequence in real-time:', sequenceNum, 'for date:', date);

        // Check if this sequence is valid for the selected date
        console.log('Checking sequences for date:', date.split('T')[0], 'equipment ID:', {{ $equipment->id }});
        fetch('{{ route($prefix . ".equipment.check-sequences", $equipment) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ date: date.split('T')[0] })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Real-time sequence check response:', data);

            if (data.success) {
                const nextSequence = data.next_sequence;

                console.log('Next expected sequence:', nextSequence, 'Entered:', sequenceNum);

                // Strict consecutive validation - alert immediately like date validation
                if (sequenceNum !== nextSequence) {
                    let message = '';
                    if (nextSequence === 1) {
                        message = `This is the first entry for ${date.split('T')[0]}. Sequence number must be 01.`;
                    } else {
                        message = `Sequence number must be ${String(nextSequence).padStart(2, '0')} (next consecutive number after ${String(nextSequence - 1).padStart(2, '0')}).`;
                    }

                    alert(message);
                    justAlerted = true;
                    // Allow time for user to edit before next check
                    setTimeout(() => {
                        justAlerted = false;
                        joSequenceInput.focus();
                        joSequenceInput.select();
                    }, 100);
                }
            }
        })
        .catch(error => {
            console.error('Error checking sequence in real-time:', error);
        });
    });

    // Initialize prefix on page load
    updateJOPrefix();

    // Handle old input for sequence field
    const oldSequence = "{{ old('jo_sequence') }}";
    if (oldSequence) {
        joSequenceInput.value = oldSequence;
        updateFullJONumber();
    }

    // Prevent backdating
    dateInput.addEventListener('change', function() {
        if (justAlertedDate) {
            justAlertedDate = false;
            return;
        }

        const selectedDateTime = new Date(this.value);
        const now = new Date();

        if (selectedDateTime > now) {
            alert('Cannot set future dates. Please select current date/time.');
            this.value = now.toISOString().slice(0, 16);
            updateJOPrefix(); // Re-update prefix after date correction
            return;
        }

        if (selectedDateTime < now) {
            alert('Cannot backdate repair records. Please select current date/time.');
            this.value = now.toISOString().slice(0, 16);
            updateJOPrefix(); // Re-update prefix after date correction
            return;
        }

        // Check if trying to backdate beyond last repair record
        console.log('Checking backdating for date:', this.value);
        checkBackdating(this.value);
    });

    function checkBackdating(selectedDateTime) {
        console.log('Calling checkBackdating API with date:', selectedDateTime);
        fetch('{{ route($prefix . ".equipment.check-latest-repair", $equipment) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ date: selectedDateTime })
        })
        .then(response => response.json())
        .then(data => {
            console.log('checkBackdating response:', data);
            if (!data.can_backdate) {
                console.log('Backdating not allowed, showing alert');
                alert(`The earliest allowed repair date is ${data.latest_date}. Please select that date or later.`);
                justAlertedDate = true;
                dateInput.value = data.latest_date;
                updateJOPrefix(); // Re-update prefix after date correction
            }
        })
        .catch(error => {
            console.error('Error checking backdating:', error);
        });
    }
})();
</script>
