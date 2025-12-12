

@php
    $currentUser = null;
    $prefix = 'admin';

    if (auth('staff')->check()) {
        $currentUser = auth('staff')->user();
        $prefix = 'staff';
    } elseif (auth('technician')->check()) {
        $currentUser = auth('technician')->user();
        $prefix = 'technician';
    } elseif (auth()->check()) {
        $currentUser = auth()->user();
        $prefix = 'admin';
    }
@endphp


<div class="row align-items-center">
        <div class="col-md-4" style="background: white; border-radius: 12px; padding: 20px;">
            
        </div>
        <div class="col-md-8">
            <h1 class="equipment-title">{{ trim(($equipment->brand ?? '') . ' | ' . ($equipment->model_number ?? '')) }}</h1>
            <div class="equipment-subtitle">{{ $equipment->equipmentType->name ?? 'Unknown Type' }} • {{ $equipment->serial_number }}</div>

            <div class="action-buttons mt-3">
                @if($currentUser && $currentUser->hasPermissionTo('equipment.edit') && Route::has($prefix . '.equipment.edit'))
                    <button type="button" class="btn btn-primary edit-equipment-btn"
                            data-equipment-id="{{ $equipment->id }}"
                            data-url="{{ route($prefix . '.equipment.edit', $equipment) }}"
                            title="Edit Equipment">
                        <i class='bx bx-edit-alt'></i> EDIT
                    </button>
                @endif

                @if($currentUser && $currentUser->hasPermissionTo('reports.view') && Route::has($prefix . '.reports.history'))
                    <a href="{{ route($prefix . '.reports.history', $equipment->id) }}" class="btn btn-primary" title="View History">
                        <i class='bx bx-history'></i> HISTORY
                    </a>
                @endif
                
                @if($currentUser && $currentUser->hasPermissionTo('equipment.delete') && Route::has($prefix . '.equipment.destroy'))
                    <form action="{{ route($prefix . '.equipment.destroy', $equipment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this equipment?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" title="Delete Equipment">
                            <i class='bx bx-trash-alt'></i> DELETE
                        </button>
                    </form>
                @endif

                @if(Route::has($prefix . '.equipment.print-qrcodes.pdf'))
                    <a href="{{ route($prefix . '.equipment.print-qrcodes.pdf', ['equipment_ids' => $equipment->id]) }}"
                       class="btn btn-primary"
                       target="_blank"
                       title="Print QR Code">
                        <i class='bx bx-printer me-1'></i> PRINT QR CODE
                    </a>
                @endif
            </div>

            <div class="mt-3">
                <p><strong>Status:</strong> <span class="badge status-{{ str_replace('_', '-', $equipment->status ?? 'unknown') }}">{{ ucfirst(str_replace('_', ' ', $equipment->status ?? 'unknown')) }}</span></p>
                <p><strong>Condition:</strong> <span class="badge status-{{ str_replace('_', '-', $equipment->condition ?? 'unknown') }}">{{ ucfirst(str_replace('_', ' ', $equipment->condition ?? 'unknown')) }}</span></p>
                <p><strong>Office:</strong> {{ $equipment->office ? $equipment->office->name : 'Not assigned' }}</p>
                @if($equipment->description)
                    <p><strong>Description:</strong> {{ $equipment->description }}</p>
                @endif
            </div>

        
        <div class="detail-card">
            <h5><i class='bx bx-cog me-2'></i>Technical Details</h5>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-barcode'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Serial Number</div>
                    <div class="detail-value">{{ $equipment->serial_number }}</div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-chip'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Model Number</div>
                    <div class="detail-value">{{ $equipment->model_number }}</div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-category'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Equipment Type</div>
                    <div class="detail-value">{{ $equipment->equipmentType->name ?? 'Unknown Type' }}</div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-calendar'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Purchase Date</div>
                    <div class="detail-value">
                        {{ $equipment->purchase_date ? $equipment->purchase_date->format('M d, Y') : 'N/A' }}
                    </div>
                </div>
            </div>

            @if($equipment->cost_of_purchase)
            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-dollar'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Cost of Purchase</div>
                    <div class="detail-value">
                        ₱{{ number_format($equipment->cost_of_purchase, 2) }}
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="equipment-image text-center">

        
                <div class="mt-3">
                    <small class="text-muted">Scan this code for quick access</small>
                </div>
                @if($equipment->qr_code_image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($equipment->qr_code_image_path))
                    <img src="{{ asset('storage/' . $equipment->qr_code_image_path) }}" alt="QR Code for {{ $equipment->model_number }}" class="img-fluid" >
                @elseif($equipment->qr_code && Route::has($prefix . '.equipment.qrcode'))
                    <img src="{{ route($prefix . '.equipment.qrcode', $equipment) }}" alt="QR Code for {{ $equipment->model_number }}" class="img-fluid"  onerror="console.log('QR Code image failed to load'); this.style.display='none';">
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="min-height: 200px;">
                        <div class="text-center">
                            <i class='bx bx-qr-scan text-6xl opacity-50 mb-2'></i>
                            <small class="d-block">No QR Code</small>
                        </div>
                    </div>
                @endif

            </div>
</div>


<script>
$(document).ready(function() {
    // Handle EDIT button clicks within the show modal
    $('.edit-equipment-btn').on('click', function() {
        var equipmentId = $(this).data('equipment-id');
        var url = $(this).data('url');
        var modal = $('#editEquipmentModal');
        var content = $('#editEquipmentContent');

        // Clear any existing session messages and SweetAlert instances
        if (window.sessionMessages) {
            window.sessionMessages = {};
        }
        
        // Close any existing SweetAlert instances immediately
        if (window.Swal) {
            Swal.close();
        }
        
        // Clear any pending toast notifications
        if (window.SweetAlert && window.SweetAlert.clearToasts) {
            window.SweetAlert.clearToasts();
        }
        
        // Add a small delay to ensure all notifications are cleared
        setTimeout(function() {
            // Close the view modal if it's open
            $('#viewEquipmentModal').modal('hide');

            // Show loading spinner
            // Use AJAX Helper to load modal content
            window.AjaxHelper.loadModal(url, '#editEquipmentModal', '#editEquipmentContent', {
                errorMessage: 'Failed to load equipment form. Please try again.',
                showSuccessAlert: false,
                showErrorAlert: false
            });
        }, 100);
    });

    // Handle form submission within edit modal
    $(document).on('submit', '#editEquipmentModal form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        // Use AJAX Helper with SweetAlert
        window.AjaxHelper.submitForm(this, {
            loadingMessage: 'Updating equipment...',
            successMessage: 'Equipment updated successfully!',
            errorMessage: 'Failed to update equipment. Please try again.',
            reloadOnSuccess: true,
            onError: function(xhr) {
                // Handle validation errors specifically
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    var errorHtml = '<div class="alert alert-danger"><ul>';
                    for (var field in errors) {
                        errorHtml += '<li>' + errors[field][0] + '</li>';
                    }
                    errorHtml += '</ul></div>';
                    $('#editEquipmentContent').prepend(errorHtml);
                }
            }
        });
    });
});
</script>
