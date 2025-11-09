@extends('layouts.app')

@push('styles')
  <link href="{{ asset('css/equipment.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/equipment.js') }}"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>

    <script>
    $(document).ready(function() {
        // Handle VIEW button clicks
        $('.view-equipment-btn').on('click', function() {
            var equipmentId = $(this).data('equipment-id');
            var url = $(this).data('url');
            var modal = $('#viewEquipmentModal');
            var content = $('#viewEquipmentContent');

            // Show loading spinner
            content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            // Load content via AJAX
            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    content.html(response);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                    content.html('<div class="alert alert-danger">Failed to load equipment details. Error: ' + xhr.status + ' - ' + error + '</div>');
                }
            });

            // Show modal
            modal.modal('show');
        });

        // Handle EDIT button clicks (both in table and in modal)
        $('.edit-equipment-btn').on('click', function() {
            var equipmentId = $(this).data('equipment-id');
            var url = $(this).data('url');
            var modal = $('#editEquipmentModal');
            var content = $('#editEquipmentContent');

            // Close the view modal if it's open
            $('#viewEquipmentModal').modal('hide');

            // Show loading spinner
            content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            // Load content via AJAX
            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    content.html(response);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                    content.html('<div class="alert alert-danger">Failed to load equipment form. Error: ' + xhr.status + ' - ' + error + '</div>');
                }
            });

            // Show modal
            modal.modal('show');
        });

        // Handle HISTORY button clicks
        $('.history-equipment-btn').on('click', function() {
            var equipmentId = $(this).data('equipment-id');
            var url = $(this).data('url');
            var modal = $('#historyEquipmentModal');
            var content = $('#historyEquipmentContent');

            // Close other modals if open
            $('#viewEquipmentModal').modal('hide');
            $('#editEquipmentModal').modal('hide');

            // Show loading spinner
            content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            // Load content via AJAX
            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    content.html(response);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                    content.html('<div class="alert alert-danger">Failed to load history form. Error: ' + xhr.status + ' - ' + error + '</div>');
                }
            });

            // Show modal
            modal.modal('show');
        });

        // Handle form submission within modals
        $(document).on('submit', '#editEquipmentModal form', function(e) {
            e.preventDefault();
            var form = $(this);
            var formData = new FormData(this);

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Close modal and reload page or update table
                    $('#editEquipmentModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    // Handle errors - show validation errors
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        // Display validation errors
                        var errorHtml = '<div class="alert alert-danger"><ul>';
                        for (var field in errors) {
                            errorHtml += '<li>' + errors[field][0] + '</li>';
                        }
                        errorHtml += '</ul></div>';
                        $('#editEquipmentContent').prepend(errorHtml);
                    } else {
                        $('#editEquipmentContent').prepend('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                    }
                }
            });
        });

        // Handle history form submission within modal
        $(document).on('submit', '#historyEquipmentModal form', function(e) {
            e.preventDefault();
            var form = $(this);
            var formData = new FormData(this);

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Close modal and reload page
                    $('#historyEquipmentModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    // Handle errors - show validation errors
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        // Display validation errors
                        var errorHtml = '<div class="alert alert-danger"><ul>';
                        for (var field in errors) {
                            errorHtml += '<li>' + errors[field][0] + '</li>';
                        }
                        errorHtml += '</ul></div>';
                        $('#historyEquipmentContent').prepend(errorHtml);
                    } else {
                        $('#historyEquipmentContent').prepend('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                    }
                }
            });
        });

        // Auto-open modals based on URL parameters (for QR scanner integration)
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);

            // Auto-open view modal
            if (urlParams.has('view_equipment')) {
                const equipmentId = urlParams.get('view_equipment');
                const viewBtn = $(`.view-equipment-btn[data-equipment-id="${equipmentId}"]`);
                if (viewBtn.length > 0) {
                    viewBtn.trigger('click');
                }
            }

            // Auto-open history modal
            if (urlParams.has('history_equipment')) {
                const equipmentId = urlParams.get('history_equipment');
                const historyBtn = $(`.history-equipment-btn[data-equipment-id="${equipmentId}"]`);
                if (historyBtn.length > 0) {
                    historyBtn.trigger('click');
                }
            }
        });
    });
    </script>
@endpush
@section('title', 'Equipment - SDMD')


@section('page_title', 'Equipment Management')
@section('page_description', 'Manage equipments and their information')



@section('content')
<div class="content">

@php
    // Determine the current user and prefix based on which guard is actually authenticated
    $currentUser = null;
    $prefix = 'admin'; // default
    
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

@if(!$currentUser || !$currentUser->hasPermissionTo('equipment.view'))
    @php abort(403) @endphp
@else


    <div class="card mb-4">
        <div class="action-buttons">
            @if($currentUser && $currentUser->hasPermissionTo('equipment.create') && Route::has($prefix . '.equipment.create'))
            <a href="{{ route($prefix . '.equipment.create') }}" class="btn btn-primary">
                <i class='bx bx-plus me-1'></i> Add New Equipment
            </a>
            @endif
            </div>
        <div class="card-body">
            <form action="{{ route($prefix . '.equipment.index') }}" method="GET" class="filter-form">
                <!-- Search Field -->
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Model, serial, or description..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All Statuses</option>
                        <option value="serviceable" {{ request('status') == 'serviceable' ? 'selected' : '' }}>Serviceable</option>
                        <option value="for_repair" {{ request('status') == 'for_repair' ? 'selected' : '' }}>For Repair</option>
                        <option value="defective" {{ request('status') == 'defective' ? 'selected' : '' }}>Defective</option>
                    </select>
                </div>

                <!-- Equipment Type Filter -->
                <div class="filter-group">
                    <label for="equipment_type">Equipment Type</label>
                    <select id="equipment_type" name="equipment_type" class="form-select">
                        <option value="all" {{ request('equipment_type') == 'all' || !request('equipment_type') ? 'selected' : '' }}>All Types</option>
                        @foreach($equipmentTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('equipment_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Office Filter -->
                <div class="filter-group">
                    <label for="office_id">Office</label>
                    <select name="office_id" id="office_id" class="form-select">
                        <option value="all" {{ request('office_id') == 'all' || !request('office_id') ? 'selected' : '' }}>All Offices</option>
                        @foreach($campuses as $campus)
                            <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                @foreach($campus->offices->where('is_active', true) as $office)
                                    <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="filter-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="all" {{ request('category_id') == 'all' || !request('category_id') ? 'selected' : '' }}>All Categories</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>


                <!-- Action Buttons -->
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route($prefix . '.equipment.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>


    <!-- Equipment Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Serial</th>
                    <th scope="col">Model</th>
                    <th scope="col">Type</th>
                    <th scope="col">Office</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipment as $item)
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">{{ $item->serial_number ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $item->model_number ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 120px;" title="{{ $item->equipmentType ? $item->equipmentType->name : 'N/A' }}">
                                {{ $item->equipmentType ? $item->equipmentType->name : 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" title="{{ $item->office ? $item->office->name : 'N/A' }}">
                                {{ $item->office ? $item->office->name : 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge status-{{ str_replace(' ', '_', strtolower($item->status ?? 'available')) }} fs-6 px-2 py-1">
                                {{ ucfirst(str_replace('_', ' ', $item->status ?? 'available')) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if($currentUser && $currentUser->hasPermissionTo('equipment.view'))
                                <button type="button" class="btn btn-sm btn-primary view-equipment-btn" 
                                        data-equipment-id="{{ $item->id }}" 
                                        data-url="{{ route($prefix . '.equipment.show', $item) }}"
                                        title="view">
                                    <i class='bx bx-show-alt'></i>
                                </button>
                                @endif
                                @if($currentUser && $currentUser->hasPermissionTo('equipment.edit') && Route::has($prefix . '.equipment.edit'))
                                <button type="button" class="btn btn-sm btn-primary edit-equipment-btn" 
                                        data-equipment-id="{{ $item->id }}" 
                                        data-url="{{ route($prefix . '.equipment.edit', $item) }}"
                                        title="edit">
                                    <i class='bx bx-edit'></i>
                                </button>
                                @endif
                                @if($currentUser && $currentUser->hasPermissionTo('history.create') && Route::has($prefix . '.equipment.history.create'))
                                <button type="button" class="btn btn-sm btn-primary history-equipment-btn" 
                                        data-equipment-id="{{ $item->id }}" 
                                        data-url="{{ route($prefix . '.equipment.history.create', $item) }}"
                                        title="Add History">
                                    <i class='bx bx-file'></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-equipment">
                                <i class='bx bx-cube'></i>
                                <h5>No Equipment Found</h5>
                                <p>Contact your administrator to add equipment to the inventory.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($equipment->hasPages())
    <div class="pagination-section">
        <div class="pagination-info">
            Showing {{ $equipment->firstItem() }} to {{ $equipment->lastItem() }} of {{ $equipment->total() }} results
        </div>
        {{ $equipment->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif

    @endif

@endsection

@push('modals')
<!-- Equipment View Modal -->
<div class="modal fade" id="viewEquipmentModal" tabindex="-1" aria-labelledby="viewEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEquipmentModalLabel">Equipment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewEquipmentContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Equipment Edit Modal -->
<div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-labelledby="editEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEquipmentModalLabel">Edit Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editEquipmentContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Equipment History Modal -->
<div class="modal fade" id="historyEquipmentModal" tabindex="-1" aria-labelledby="historyEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyEquipmentModalLabel">Add History Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="historyEquipmentContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush
