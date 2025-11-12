@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/accounts/accounts.css') }}" rel="stylesheet">
    <style>
    </style>
@endpush

@section('title', auth()->user()->is_admin ? 'SDMD Admin - Accounts' : (auth()->user()->hasRole('technician') ? 'SDMD Technician - Accounts' : (auth()->user()->hasRole('staff') ? 'SDMD Staff - Accounts' : 'SDMD Accounts')))

@section('page_title', 'Accounts Management')
@section('page_description', 'Manage all user accounts and permissions')

@section('content')
<div class="content">
@if(!auth()->user()->hasPermissionTo('users.view'))
    @php abort(403) @endphp
@else
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class='bx bx-check-circle me-2'></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class='bx bx-error-circle me-2'></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="action-buttons">
        @if(auth()->user()->hasPermissionTo('users.create'))
        <a href="{{ route('accounts.form') }}" class="btn btn-primary btn-sm">
            <i class='bx bx-plus me-1'></i> Add User
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
                            @php
                                $activeRoles = $user->roles->filter(function($role) {
                                    $expiresAt = $role->pivot->expires_at;
                                    return is_null($expiresAt) || $expiresAt > now();
                                });
                                $primaryRole = $activeRoles->first();
                            @endphp
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $primaryRole?->display_name ?? 'No role' }}">
                                {{ $primaryRole?->display_name ?? 'No role' }}
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
                                <a href="{{ route('accounts.show', $user) }}"
                                   class="btn btn-sm btn-primary"
                                   title="view">
                                    <i class='bx bx-show-alt'></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('users.edit'))
                                <a href="{{ route('accounts.edit', $user) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="edit">
                                    <i class='bx bx-edit'></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin && $activeRoles->contains('name', 'technician'))
                                <button type="button" class="btn btn-primary btn-sm"
                                        onclick="grantTempAdmin({{ $user->id }}, '{{ $user->name }}')"
                                        title="Grant Temporary Admin Access">
                                    <i class='bx bx-time-five'></i>
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

@endsection
@push('scripts')
<script>
function grantTempAdmin(userId, userName) {
    // Create modal HTML
    const modalHtml = `
        <div class="modal fade" id="tempAdminModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Grant Temporary Admin Access</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Grant temporary admin access to <strong>${userName}</strong>?</p>
                        <form id="tempAdminForm">
                            <div class="mb-3">
                                <label for="expires_at" class="form-label">Expires At</label>
                                <input type="datetime-local" class="form-control" id="expires_at" name="expires_at"
                                       min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required>
                                <small class="form-text text-muted">Select when the temporary admin access should expire</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning" onclick="submitTempAdmin(${userId})">
                            <i class='bx bx-time-five me-1'></i> Grant Access
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if present
    const existingModal = document.getElementById('tempAdminModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('tempAdminModal'));
    modal.show();
}

function submitTempAdmin(userId) {
    const expiresAt = document.getElementById('expires_at').value;
    const formData = new FormData();
    formData.append('expires_at', expiresAt);
    formData.append('_token', '{{ csrf_token() }}');

    fetch(`/admin/accounts/${userId}/grant-temp-admin`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('tempAdminModal'));
            modal.hide();

            // Show success message
            showAlert('success', data.message);

            // Reload page after short delay
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showAlert('danger', data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while granting temporary admin access');
    });
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Add to top of content
    const content = document.querySelector('.content');
    content.insertAdjacentHTML('afterbegin', alertHtml);
}
</script>
@endpush

