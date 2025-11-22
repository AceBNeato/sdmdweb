@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/accounts/accounts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/accounts/accounts-show.css') }}" rel="stylesheet">
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
        <a href="{{ route('admin.accounts.form') }}" class="btn btn-primary btn-sm">
            <i class='bx bx-plus me-1'></i> Add User
        </a>
        @endif
        
        @if(auth()->user()->is_admin)
        <a href="{{ route('admin.rbac.roles.index') }}" class="btn btn-secondary btn-sm">
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
                               placeholder="Search by name or email..." value="{{ request('search') }}">
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
                            <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
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
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(request()->hasAny(['search', 'role', 'office', 'status']))
        <div class="alert alert-info mb-3">
            <i class='bx bx-info-circle me-2'></i>
            Found {{ $users->total() }} user(s) matching your criteria.
            @if(request()->has('search'))
                <br>Search: <strong>{{ request('search') }}</strong>
            @endif
            @if(request()->has('role') && request('role') !== 'all')
                <br>Role: <strong>{{ $roles->firstWhere('name', request('role'))->display_name ?? request('role') }}</strong>
            @endif
            @if(request()->has('office') && request('office') !== 'all')
                @php
                    $selectedOffice = null;
                    foreach($campuses as $campus) {
                        $office = $campus->offices->find(request('office'));
                        if ($office) {
                            $selectedOffice = $office;
                            break;
                        }
                    }
                @endphp
                <br>Office: <strong>{{ $selectedOffice ? $selectedOffice->name : request('office') }}</strong>
            @endif
            @if(request()->has('status') && request('status') !== 'all')
                <br>Status: <strong>{{ request('status') === 'active' ? 'Active' : 'Inactive' }}</strong>
            @endif
        </div>
    @endif

    <!-- Accounts Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Campus</th>
                    <th scope="col">Office</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">{{ $user->first_name . ' ' . $user->last_name }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $user->email }}</div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $user->role?->display_name ?? 'No role' }}">
                                {{ $user->role?->display_name ?? 'No role' }}
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 120px;" title="{{ $user->campus?->name ?? 'N/A' }}">
                                {{ $user->campus?->name ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 120px;" title="{{ $user->office?->name ?? 'N/A' }}">
                                {{ $user->office?->name ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(auth()->user()->hasPermissionTo('users.view'))
                                <button type="button" class="btn btn-sm btn-primary view-user-btn"
                                        data-user-id="{{ $user->id }}"
                                        data-url="{{ route('admin.accounts.show', $user) }}"
                                        title="view">
                                    <i class='bx bx-show-alt'></i>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermissionTo('users.edit'))
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary edit-user-btn"
                                        data-url="{{ route('admin.accounts.edit', $user) }}"
                                        title="Edit">
                                    <i class='bx bx-edit'></i>
                                </button>
                                
                                <button type="button"
                                        class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} toggle-status-btn"
                                        data-user-id="{{ $user->id }}"
                                        data-url="{{ route('admin.accounts.toggle-status', $user) }}"
                                        title="{{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}">
                                    <i class='bx {{ $user->is_active ? 'bx-user-x' : 'bx-user-check' }}'></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="empty-equipment">
                                <i class='bx bx-user-x'></i>
                                <h5>No Users Found</h5>
                                <p>Get started by adding a new user</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="pagination-section">
        <div class="pagination-info">
            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
        </div>
        {{ $users->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif
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

@endsection

