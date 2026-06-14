@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/office.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/offices-index.js') }}"></script>
@endpush

@section('title', 'Offices - SDMD')

@section('page_title', 'Offices Management')
@section('page_description', 'Manage all office locations and contact information')

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

@if(!$currentUser || !$currentUser->hasPermissionTo('offices.view'))
    @php abort(403) @endphp
@else

    <!-- Page Header with Filters Card -->
    <div class="card mb-4">
        <div class="action-buttons">
            @if($currentUser && $currentUser->hasPermissionTo('offices.create') && Route::has($prefix . '.offices.create'))
            <button type="button"
                    class="btn btn-primary add-office-btn"
                    data-url="{{ route($prefix . '.offices.create') }}"
                    title="Add office">
                <i class='bx bx-plus me-1'></i>
                <span>Add Office</span>
            </button>
            @endif
        </div>
        <div class="card-body">
            <form action="{{ route($prefix . '.offices.index') }}" method="GET" class="filter-form">
                <!-- Search Field -->
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Name, email, or location..."
                               value="{{ request('search') }}"
                               hx-get="{{ route($prefix . '.offices.index') }}"
                               hx-target="#offices-table-container"
                               hx-trigger="keyup delay:500ms, search"
                               hx-include=".filter-form"
                               hx-push-url="true"
                               hx-indicator=".loader-indicator">
                    </div>
                </div>

                <!-- Campus Filter -->
                <div class="filter-group">
                    <label for="campus_id">Campus</label>
                    <select name="campus_id" id="campus_id" class="form-select">
                        <option value="all" {{ request('campus_id') == 'all' || !request('campus_id') ? 'selected' : '' }}>All Campuses</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}{{ $campus->code ? ' (' . $campus->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route($prefix . '.offices.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>



    @include('offices.partials.table')

    @endif
@endsection

@push('modals')
<!-- Office Create Modal -->
<div class="modal fade" id="officeCreateModal" tabindex="-1" aria-labelledby="officeCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="officeCreateModalLabel">
                    <i class='bx bx-building me-2'></i>Add New Office
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="officeCreateContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Office View Modal -->
<div class="modal fade" id="officeViewModal" tabindex="-1" aria-labelledby="officeViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="officeViewModalLabel">
                    <i class='bx bx-building me-2'></i>Office Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="officeViewContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Office Edit Modal -->
<div class="modal fade" id="officeEditModal" tabindex="-1" aria-labelledby="officeEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="officeEditModalLabel">
                    <i class='bx bx-edit me-2'></i>Edit Office
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="officeEditContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('styles')
<style>
/* Fix modal z-index issues */
.modal {
    z-index: 1055 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
}

.modal-dialog {
    z-index: 1056 !important;
}

.modal-content {
    z-index: 1057 !important;
}

/* Ensure modals are above everything */
.modal.show {
    z-index: 1055 !important;
}

/* Fix backdrop overlay */
.modal-backdrop.show {
    z-index: 1050 !important;
}

/* Form Section Styles */
.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
}

.form-section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
}

.field-container {
    margin-bottom: 1rem;
}

.field-container .form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.field-container .form-label.required::after {
    content: " *";
    color: #dc3545;
}

.field-container .form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #495057;
}

.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>
@endpush

@push('scripts')
<!-- Office JavaScript handled by offices-index.js -->
@endpush
