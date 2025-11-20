@php
    $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');
    $isAjax = request()->ajax();
@endphp

@if($isAjax)
    <!-- AJAX version - only return the form content -->
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

        /* Required field indicator */
        .required::after {
            content: " *";
            color: #dc3545;
        }

        /* Field container for consistent spacing */
        .field-container {
            position: relative;
        }
    </style>

    <form method="POST" action="{{ route($prefix . '.equipment.history.update', [$equipment, $history]) }}" id="editHistoryForm">
        @csrf
        @method('PUT')

        <div class="modal-body">
            <!-- Equipment Info -->
            <div class="alert alert-info">
                <h6 class="alert-heading mb-2">
                    <i class='bx bx-info-circle me-1'></i>Equipment Information
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Equipment:</strong> {{ $equipment->model_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Serial:</strong> {{ $equipment->serial_number }}
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-md-6">
                        <strong>JO Number:</strong> {{ $history->jo_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Current Status:</strong>
                        <span class="badge bg-{{ $equipment->status === 'serviceable' ? 'success' : ($equipment->status === 'for_repair' ? 'warning' : 'danger') }}">
                            {{ ucfirst(str_replace('_', ' ', $equipment->status)) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Taken Field -->
            <div class="mb-3">
                <div class="field-container">
                    <label for="action_taken" class="form-label required">Action Taken</label>
                    <textarea class="form-control @error('action_taken') is-invalid @enderror"
                              id="action_taken" name="action_taken" rows="4"
                              placeholder="Describe the maintenance or service action performed..." required>{{ old('action_taken', $history->action_taken) }}</textarea>
                    @error('action_taken')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Equipment Status Field -->
            <div class="mb-3">
                <div class="field-container">
                    <label for="equipment_status" class="form-label required">Equipment Status</label>
                    <select class="form-select @error('equipment_status') is-invalid @enderror"
                            id="equipment_status" name="equipment_status" required>
                        <option value="" disabled>Select Status</option>
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

            <!-- Hidden remarks field that gets auto-updated -->
            <input type="hidden" id="remarks" name="remarks" value="{{ old('remarks') }}">
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-save me-1'></i> Update History Entry
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class='bx bx-x me-1'></i> Cancel
            </button>
        </div>
    </form>

    <script>
    // Initialize immediately since this is loaded via AJAX into modal
    (function() {
        const statusSelect = document.getElementById('equipment_status');
        const remarksHidden = document.getElementById('remarks');

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

            remarksHidden.value = remarksText;
        }

        // Update remarks when status changes
        statusSelect.addEventListener('change', updateRemarks);

        // Only use old remarks if there was a validation error, otherwise use status-based remarks
        const oldRemarks = "{{ old('remarks') }}";
        if (oldRemarks && "{{ $errors->has('remarks') }}") {
            remarksHidden.value = oldRemarks;
        } else {
            // Initialize remarks based on current status
            updateRemarks();
        }
    })();
    </script>

@else
    <!-- Full page version -->
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

        /* Required field indicator */
        .required::after {
            content: " *";
            color: #dc3545;
        }

        /* Field container for consistent spacing */
        .field-container {
            position: relative;
        }
    </style>

    <div class="modal fade" id="editHistoryModal" tabindex="-1" aria-labelledby="editHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editHistoryModalLabel">
                        <i class='bx bx-edit me-2'></i>Edit History Entry
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route($prefix . '.equipment.history.update', [$equipment, $history]) }}" id="editHistoryForm">
                    @csrf
                    @method('PUT')

                    <div class="modal-body">
                        <!-- Equipment Info -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading mb-2">
                                <i class='bx bx-info-circle me-1'></i>Equipment Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Equipment:</strong> {{ $equipment->model_number }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Serial:</strong> {{ $equipment->serial_number }}
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-md-6">
                                    <strong>JO Number:</strong> {{ $history->jo_number }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Current Status:</strong>
                                    <span class="badge bg-{{ $equipment->status === 'serviceable' ? 'success' : ($equipment->status === 'for_repair' ? 'warning' : 'danger') }}">
                                        {{ ucfirst(str_replace('_', ' ', $equipment->status)) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Taken Field -->
                        <div class="mb-3">
                            <div class="field-container">
                                <label for="action_taken" class="form-label required">Action Taken</label>
                                <textarea class="form-control @error('action_taken') is-invalid @enderror"
                                          id="action_taken" name="action_taken" rows="4"
                                          placeholder="Describe the maintenance or service action performed..." required>{{ old('action_taken', $history->action_taken) }}</textarea>
                                @error('action_taken')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Equipment Status Field -->
                        <div class="mb-3">
                            <div class="field-container">
                                <label for="equipment_status" class="form-label required">Equipment Status</label>
                                <select class="form-select @error('equipment_status') is-invalid @enderror"
                                        id="equipment_status" name="equipment_status" required>
                                    <option value="" disabled>Select Status</option>
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

                        <!-- Hidden remarks field that gets auto-updated -->
                        <input type="hidden" id="remarks" name="remarks" value="{{ old('remarks') }}">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save me-1'></i> Update History Entry
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class='bx bx-x me-1'></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Initialize immediately for full page version
    (function() {
        const statusSelect = document.getElementById('equipment_status');
        const remarksHidden = document.getElementById('remarks');

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

            remarksHidden.value = remarksText;
        }

        // Update remarks when status changes
        statusSelect.addEventListener('change', updateRemarks);

        // Only use old remarks if there was a validation error, otherwise use status-based remarks
        const oldRemarks = "{{ old('remarks') }}";
        if (oldRemarks && "{{ $errors->has('remarks') }}") {
            remarksHidden.value = oldRemarks;
        } else {
            // Initialize remarks based on current status
            updateRemarks();
        }
    })();
    </script>
@endif
