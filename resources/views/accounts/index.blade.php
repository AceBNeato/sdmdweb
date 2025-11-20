@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/accounts/accounts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/accounts/accounts-show.css') }}" rel="stylesheet">
@endpush

@section('title', auth()->user()->is_admin ? 'SDMD Admin - Accounts' : (auth()->user()->hasRole('technician') ? 'SDMD Technician - Accounts' : (auth()->user()->hasRole('staff') ? 'SDMD Staff - Accounts' : 'SDMD Accounts')))

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
    </div>

    <!-- Search and Filter Card -->
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.accounts.index') }}" method="GET" class="filter-form">
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


                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(request()->hasAny(['search', 'role', 'office']))
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
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $user->roles->first()?->display_name ?? 'No role' }}">
                                {{ $user->roles->first()?->display_name ?? 'No role' }}
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
                                <button type="button" class="btn btn-sm btn-outline-secondary edit-user-btn"
                                        data-user-id="{{ $user->id }}"
                                        data-url="{{ route('admin.accounts.edit', ['user' => $user, 'modal' => 1]) }}"
                                        title="edit">
                                    <i class='bx bx-edit'></i>
                                </button>
                                @endif
                                @if(auth()->user()->is_super_admin && auth()->id() !== $user->id)
                                <form action="{{ route('admin.accounts.destroy', $user) }}" method="POST" class="delete-user-form d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="password" class="delete-user-password-input">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary delete-user-btn"
                                            data-user-name="{{ $user->name }}"
                                            title="delete">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
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

<!-- Delete User Confirmation Modal -->
<div class="modal fade delete-user-modal" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <span class="badge rounded-pill bg-danger-subtle text-danger fw-semibold mb-2"><i class='bx bx-error-circle me-1'></i> High Risk Action</span>
                    <h5 class="modal-title text-danger fw-bold mb-1" id="deleteUserModalLabel">Confirm User Deletion</h5>
                    <p class="modal-subtitle text-muted mb-0">Please review the details carefully before proceeding.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="delete-modal-alert mb-4">
                    <div class="delete-modal-icon">
                        <i class='bx bx-user-x'></i>
                    </div>
                    <div class="delete-modal-copy">
                        <p class="fw-semibold mb-1">You are about to permanently delete <strong id="deleteUserName"></strong>.</p>
                        <p class="text-muted mb-0">This will revoke all access and remove associated records that depend on this account. This action cannot be undone.</p>
                    </div>
                </div>

                <div class="alert alert-danger d-none" id="deleteUserError" role="alert"></div>

                <label for="deleteUserPassword" class="form-label fw-semibold">Enter your password to confirm</label>
                <div class="input-group delete-password-group mb-2">
                    <span class="input-group-text"><i class='bx bx-lock-alt'></i></span>
                    <input type="password" class="form-control delete-password-input" id="deleteUserPassword" placeholder="Current password" autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary password-toggle" id="deleteUserPasswordToggle" aria-label="Toggle password visibility">
                        <i class='bx bx-show-alt'></i>
                    </button>
                </div>
                <p class="text-muted small mb-0">For security reasons, only super administrators who confirm their password can delete user accounts.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-primary" id="confirmDeleteUserBtn">
                    <span class="spinner-border spinner-border-sm d-none" id="deleteUserLoadingSpinner" role="status" aria-hidden="true"></span>
                    <span class="btn-label"><i class='bx bx-trash'></i> Delete User</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

