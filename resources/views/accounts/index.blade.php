@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/accounts/accounts.css') }}?v={{ filemtime(public_path('css/accounts/accounts.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/accounts/accounts-show.css') }}?v={{ filemtime(public_path('css/accounts/accounts-show.css')) }}" rel="stylesheet">
@endpush

@section('title', auth()->user()->is_admin ? 'SDMD Admin - Accounts' : (auth()->user()->role?->name === 'technician' ? 'SDMD Technician - Accounts' : (auth()->user()->role?->name === 'staff' ? 'SDMD Staff - Accounts' : 'SDMD Accounts')))

@section('page_title', 'Accounts Management')
@section('page_description', 'Manage all user accounts and permissions')

@section('content')
<div class="content">
@if(!auth()->user()->hasPermissionTo('users.view'))
    @php abort(403) @endphp
@else
    <div class="action-buttons">
        @if(auth()->user()->hasPermissionTo('users.create'))
        <button type="button" class="btn btn-primary add-user-btn" data-url="{{ route('admin.accounts.form') }}" title="Add User">
            <i class='bx bx-plus me-1'></i> Add User
        </button>
        @endif
        
        @if(auth()->user()->is_admin)
        <a href="{{ route('admin.rbac.roles.index') }}" class="btn btn-secondary">
            <i class='bx bx-shield-alt me-1'></i> RBAC Management
        </a>
        @endif
    </div>

    <!-- Search and Filter Card -->
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('accounts.index') }}" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Search by name or email..." value="{{ request('search') }}"
                               hx-get="{{ route('accounts.index') }}"
                               hx-target="#accounts-table-container"
                               hx-trigger="keyup delay:500ms, search"
                               hx-include=".filter-form"
                               hx-push-url="true"
                               hx-indicator=".loader-indicator">
                    </div>
                </div>

                <div class="filter-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="form-select">
                        <option value="all">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ $role->display_name ?? $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="office">Office</label>
                    <select id="office" name="office" class="form-select">
                        <option value="all">All Offices</option>
                        @foreach($campuses as $campus)
                            <optgroup label="{{ $campus->name }}{{ $campus->code ? ' (' . $campus->code . ')' : '' }}">
                                @foreach($campus->offices->where('is_active', true) as $office)
                                    <option value="{{ $office->id }}" {{ request('office') == $office->id ? 'selected' : '' }}>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>


                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary loader-indicator">
                        <i class='bx bx-filter-alt me-1 htmx-hide-loading'></i>
                        <span class="spinner-border spinner-border-sm htmx-show-loading d-none" role="status" aria-hidden="true"></span>
                        <span class="htmx-hide-loading">Apply Filters</span>
                        <span class="htmx-show-loading d-none">Loading...</span>
                    </button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- HTMX Dynamic Table Container -->
    <div id="accounts-table-container">
        @include('accounts.partials.table')
    </div>
</div>


@endif

@push('scripts')
<script src="{{ asset('js/accounts-index.js') }}"></script>
@endpush

<!-- User View Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewUserContent">
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

<!-- User Edit Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editUserContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Create Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="createUserContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Automatic Search Functionality
    const searchInput = document.getElementById('search');
    const roleFilter = document.getElementById('role');
    const campusFilter = document.getElementById('campus');
    const officeFilter = document.getElementById('office');
    const statusFilter = document.getElementById('status');
    const userRows = document.querySelectorAll('tbody tr');

    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        const roleValue = roleFilter.value.toLowerCase();
        const campusValue = campusFilter.value.toLowerCase();
        const officeValue = officeFilter.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();

        userRows.forEach(row => {
            const name = row.dataset.name.toLowerCase();
            const email = row.dataset.email.toLowerCase();
            const role = row.dataset.role.toLowerCase();
            const campus = row.dataset.campus.toLowerCase();
            const office = row.dataset.office.toLowerCase();
            const text = row.textContent.toLowerCase();

            const matchesSearch = searchTerm === '' || 
                text.includes(searchTerm) || 
                name.includes(searchTerm) || 
                email.includes(searchTerm);

            const matchesRole = roleValue === 'all' || role.includes(roleValue);
            const matchesCampus = campusValue === 'all' || campus.includes(campusValue);
            const matchesOffice = officeValue === 'all' || office.includes(officeValue);
            const matchesStatus = statusValue === 'all' || text.includes(statusValue);

            if (matchesSearch && matchesRole && matchesCampus && matchesOffice && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Add event listeners for real-time filtering
    searchInput.addEventListener('input', filterUsers);
    roleFilter.addEventListener('change', filterUsers);
    campusFilter.addEventListener('change', filterUsers);
    officeFilter.addEventListener('change', filterUsers);
    statusFilter.addEventListener('change', filterUsers);
});
</script>
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
</style>
@endpush

@endsection
