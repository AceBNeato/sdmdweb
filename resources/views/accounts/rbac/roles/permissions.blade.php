@extends('layouts.app')

@section('page_title', 'Role Permission Matrix')
@section('page_description', 'Manage permissions for all roles')

@push('styles')
    <style>
        .rbac-permissions-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .permissions-matrix {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .matrix-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }
        
        .matrix-table th {
            background: #f8f9fa;
            padding: 1rem 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .matrix-table th.role-header {
            min-width: 150px;
        }
        
        .matrix-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .matrix-table tr:hover {
            background: #f8f9fa;
        }
        
        .permission-group-header {
            background: #e3f2fd;
            font-weight: 600;
            color: #1976d2;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .permission-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .permission-description {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .permission-checkbox {
            text-align: center;
        }
        
        .permission-checkbox input[type="checkbox"] {
            transform: scale(1.2);
            cursor: pointer;
        }
        
        .permission-checkbox input[type="checkbox"]:checked {
            accent-color: #28a745;
        }

        /* Muted state for permissions that are not editable for a given role */
        .permission-checkbox.disabled-cell {
            opacity: 0.45;
        }

        .permission-checkbox.disabled-cell input[type="checkbox"] {
            cursor: not-allowed;
        }
        
        .role-column {
            min-width: 120px;
            text-align: center;
        }
        
        .role-name {
            font-weight: 600;
            color: #495057;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .role-count {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .admin-role {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 0.25rem;
        }
        
        .matrix-actions {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        
        .btn-update {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .btn-update:hover {
            background: #218838;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .btn-reset:hover {
            background: #5a6268;
        }
        
        .summary-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #495057;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
@endpush

@section('content')
<div class="rbac-permissions-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-2">Role Permission Matrix</h1>
            <p class="text-muted">Manage permissions for all roles in one place</p>
        </div>
        <div>
            <a href="{{ route('admin.rbac.roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Roles
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="summary-stats">
        <div class="stat-card">
            <div class="stat-number">{{ $roles->count() }}</div>
            <div class="stat-label">Total Roles</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $permissions->count() }}</div>
            <div class="stat-label">Total Permissions</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $roles->sum(function($role) { return $role->permissions->count(); }) }}</div>
            <div class="stat-label">Total Assignments</div>
        </div>
    </div>

    <!-- Permissions Matrix -->
    <div class="permissions-matrix">
        <form action="{{ route('admin.rbac.roles.update-permissions') }}" method="POST" id="permissionsForm">
            @csrf
            
            <div class="matrix-actions">
                <div>
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save me-2"></i>Update All Permissions
                    </button>
                    <button type="button" class="btn-reset" onclick="resetForm()">
                        <i class="fas fa-undo me-2"></i>Reset Changes
                    </button>
                </div>
                <div class="text-muted">
                    <small>Click checkboxes to toggle permissions. Changes are saved when you click "Update All Permissions".</small>
                </div>
            </div>

            <table class="matrix-table">
                <thead class="sticky-header">
                    <tr>
                        <th class="role-header">Permission / Role</th>
                        @foreach($roles as $role)
                            <th class="role-column" data-role-id="{{ $role->id }}">
                                <span class="role-name {{ $role->name === 'super-admin' || $role->name === 'admin' ? 'admin-role' : '' }}">
                                    {{ $role->display_name }}
                                </span>
                                <div class="role-count">{{ $role->permissions->count() }} perms</div>
                                <a href="#" class="text-primary small d-block mt-1 role-select-all" style="cursor: pointer;">
                                    <i class="fas fa-check-square me-1"></i>Select All
                                </a>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        $groupedPermissions = $permissions->groupBy('group');

                        // Whitelisted permissions that Staff are allowed to have
                        $staffAllowedPermissions = [
                            'qr.scan',
                            'equipment.view', 'equipment.edit', 'equipment.create',
                            'reports.view', 'reports.generate',
                            'profile.view', 'profile.update',
                        ];

                        // Whitelisted permissions that Technicians are allowed to have
                        $technicianAllowedPermissions = [
                            'qr.scan',
                            'equipment.view', 'equipment.edit', 'equipment.create',
                            'reports.view', 'reports.generate',
                            'profile.view', 'profile.update',
                            'history.create', 'history.store', 'history.edit',
                        ];
                    @endphp
                    
                    @foreach($groupedPermissions as $group => $groupPermissions)
                        <tr>
                            <td colspan="{{ $roles->count() + 1 }}" class="permission-group-header">
                                @switch($group)
                                    @case('users')
                                        <i class="fas fa-users me-2"></i>User Management
                                        @break
                                    @case('roles')
                                        <i class="fas fa-user-tag me-2"></i>Role Management
                                        @break
                                    @case('equipment')
                                        <i class="fas fa-tools me-2"></i>Equipment Management
                                        @break
                                    @case('maintenance')
                                        <i class="fas fa-wrench me-2"></i>Maintenance Management
                                        @break
                                    @case('history')
                                        <i class="fas fa-history me-2"></i>Equipment History
                                        @break
                                    @case('reports')
                                        <i class="fas fa-chart-bar me-2"></i>Reports
                                        @break
                                    @case('settings')
                                        <i class="fas fa-cog me-2"></i>System Settings
                                        @break
                                    @default
                                        <i class="fas fa-shield-alt me-2"></i>{{ ucfirst($group) }}
                                @endswitch
                            </td>
                        </tr>
                        
                        @foreach($groupPermissions as $permission)
                            <tr id="permission-{{ $permission->id }}">
                                <td>
                                    <div class="permission-name">{{ $permission->display_name }}</div>
                                    <div class="permission-description">{{ $permission->description }}</div>
                                </td>
                                
                                @foreach($roles as $role)
                                    @php
                                        $isStaffRole = $role->name === 'staff';
                                        $isTechnicianRole = $role->name === 'technician';
                                        $isRestrictedRole = $isStaffRole || $isTechnicianRole;

                                        // By default, permissions are editable
                                        $isAllowedForRole = true;
                                        if ($isStaffRole) {
                                            $isAllowedForRole = in_array($permission->name, $staffAllowedPermissions, true);
                                        } elseif ($isTechnicianRole) {
                                            $isAllowedForRole = in_array($permission->name, $technicianAllowedPermissions, true);
                                        }

                                        // For staff/technician, anything outside their whitelist is muted/disabled
                                        $isDisabledCell = $isRestrictedRole && !$isAllowedForRole;
                                    @endphp
                                    <td class="permission-checkbox{{ $isDisabledCell ? ' disabled-cell' : '' }}">
                                        <input type="checkbox" 
                                               name="permissions[{{ $role->id }}][{{ $permission->id }}]" 
                                               id="role_{{ $role->id }}_perm_{{ $permission->id }}"
                                               value="{{ $permission->id }}"
                                               {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}
                                               data-role="{{ $role->id }}"
                                               data-permission="{{ $permission->id }}"
                                               @if($isDisabledCell)
                                                   disabled
                                                   data-locked="1"
                                               @else
                                                   onchange="markChanged()"
                                               @endif
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let hasChanges = false;
const originalState = {};

// Store original state
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]'))
        .filter(checkbox => checkbox.dataset.locked !== '1');
    checkboxes.forEach(checkbox => {
        originalState[checkbox.id] = checkbox.checked;
    });
});

function markChanged() {
    hasChanges = true;
    updateButtonStates();
}

function updateButtonStates() {
    const updateBtn = document.querySelector('.btn-update');
    const resetBtn = document.querySelector('.btn-reset');
    
    if (hasChanges) {
        updateBtn.classList.remove('btn-secondary');
        updateBtn.classList.add('btn-success');
        resetBtn.classList.remove('btn-outline-secondary');
        resetBtn.classList.add('btn-warning');
    } else {
        updateBtn.classList.remove('btn-success');
        updateBtn.classList.add('btn-secondary');
        resetBtn.classList.remove('btn-warning');
        resetBtn.classList.add('btn-outline-secondary');
    }
}

function resetForm() {
    // Only reset editable (non-locked) checkboxes
    const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]'))
        .filter(checkbox => checkbox.dataset.locked !== '1');
    checkboxes.forEach(checkbox => {
        checkbox.checked = originalState[checkbox.id];
    });
    hasChanges = false;
    updateButtonStates();
}

// Handle form submission to collect all checkbox data
document.getElementById('permissionsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    
    // Collect all role permissions (including locked ones for admin/superadmin)
    const roles = @json($roles->pluck('id'));
    roles.forEach(roleId => {
        // Include ALL checked permissions for this role (locked or not)
        const checkedBoxes = document.querySelectorAll(`input[data-role="${roleId}"]:checked`);
        const permissionIds = Array.from(checkedBoxes).map(cb => cb.value);
        
        // Add each permission as separate array item
        permissionIds.forEach(permissionId => {
            formData.append(`permissions[${roleId}][${permissionId}]`, permissionId);
        });
        
        // Ensure role exists even if no permissions
        if (permissionIds.length === 0) {
            formData.append(`permissions[${roleId}]`, '');
        }
    });
    
    // Submit via fetch
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear unsaved changes flag to prevent browser warning on reload
            hasChanges = false;
            
            // Show success message with Continue button
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                confirmButtonText: 'Continue',
                confirmButtonColor: '#28a745',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'Something went wrong'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Something went wrong while updating permissions'
        });
    });
});

// Warn before leaving if there are unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        return e.returnValue;
    }
});

// Add select all functionality for columns
document.addEventListener('DOMContentLoaded', function() {
    const selectLinks = document.querySelectorAll('.role-select-all');

    selectLinks.forEach(selectLink => {
        selectLink.addEventListener('click', function(e) {
            e.preventDefault();

            const header = this.closest('.role-column');
            if (!header) return;

            const roleId = header.dataset.roleId;
            if (!roleId) return;

            const columnCheckboxes = Array.from(document.querySelectorAll(`input[data-role="${roleId}"]`))
                .filter(checkbox => checkbox.dataset.locked !== '1');
            if (columnCheckboxes.length === 0) {
                return;
            }

            const allChecked = columnCheckboxes.every(cb => cb.checked);

            columnCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });

            markChanged();

            this.innerHTML = allChecked
                ? '<i class="fas fa-square me-1"></i>Select All'
                : '<i class="fas fa-check-square me-1"></i>Deselect All';
        });
    });
});

// Add select all functionality for rows
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr:not(.permission-group-header)');
    
    rows.forEach(row => {
        const permissionCell = row.querySelector('td:first-child');
        if (!permissionCell) return;
        
        const selectLink = document.createElement('a');
        selectLink.href = '#';
        selectLink.className = 'text-primary small';
        selectLink.innerHTML = '<i class="fas fa-check-square me-1"></i>All';
        selectLink.style.cursor = 'pointer';
        selectLink.style.marginLeft = '0.5rem';
        
        const permissionName = permissionCell.querySelector('.permission-name');
        if (permissionName) {
            permissionName.appendChild(selectLink);
        }
        
        selectLink.addEventListener('click', function(e) {
            e.preventDefault();
            const checkboxes = Array.from(row.querySelectorAll('input[type="checkbox"]'))
                .filter(checkbox => checkbox.dataset.locked !== '1');
            const allChecked = checkboxes.every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
                markChanged();
            });
            
            selectLink.innerHTML = allChecked ? 
                '<i class="fas fa-square me-1"></i>None' : 
                '<i class="fas fa-check-square me-1"></i>All';
        });
    });
});
</script>
@endpush
