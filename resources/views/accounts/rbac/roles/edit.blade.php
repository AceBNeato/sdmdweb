@extends('layouts.app')

@section('page_title', 'Edit Role')
@section('page_description', 'Edit role and manage permissions')

@push('styles')
    <style>
        .rbac-edit-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .role-form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .permission-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
        }
        
        .permission-group h5 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #dee2e6;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .permission-item input[type="checkbox"] {
            margin-right: 0.75rem;
            transform: scale(1.1);
        }
        
        .permission-item label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }
        
        .permission-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .permission-description {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .role-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .role-info h5 {
            color: #1976d2;
            margin-bottom: 0.5rem;
        }
        
        .permission-count {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
<div class="rbac-edit-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-2">Edit Role: {{ $role->display_name }}</h1>
            <p class="text-muted">Manage role details and assigned permissions</p>
        </div>
        <div>
            <span class="permission-count">{{ $role->permissions->count() }} permissions</span>
        </div>
    </div>

    <!-- Role Info -->
    <div class="role-info">
        <h5><i class="fas fa-info-circle me-2"></i>Role Information</h5>
        <div class="row">
            <div class="col-md-6">
                <strong>Role Name:</strong> {{ $role->name }}<br>
                <strong>Display Name:</strong> {{ $role->display_name }}
            </div>
            <div class="col-md-6">
                <strong>Description:</strong> {{ $role->description }}<br>
                <strong>Users with this role:</strong> {{ $role->users()->count() }}
            </div>
        </div>
        <div class="mt-2">
            <small class="text-info">
                <i class="fas fa-shield-alt me-1"></i>
                This role provides permissions through role-based access control (RBAC) only.
            </small>
        </div>
    </div>

    <!-- Edit Form -->
    <form action="{{ route('admin.rbac.roles.update', $role) }}" method="POST">
        @csrf
        
        <div class="role-form-card">
            <h4 class="mb-4">Role Details</h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="{{ $role->display_name }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description" 
                               value="{{ $role->description }}" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="role-form-card">
            <h4 class="mb-4">Assigned Permissions</h4>
            
            <div class="permissions-grid">
                @php
                    $groupedPermissions = $permissions->groupBy('group');
                @endphp
                
                @foreach($groupedPermissions as $group => $groupPermissions)
                    <div class="permission-group">
                        <h5>
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
                        </h5>
                        
                        @foreach($groupPermissions as $permission)
                            <div class="permission-item">
                                <input type="checkbox" 
                                       name="permissions[]" 
                                       value="{{ $permission->id }}" 
                                       id="permission_{{ $permission->id }}"
                                       {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                <label for="permission_{{ $permission->id }}">
                                    <div class="permission-name">{{ $permission->display_name }}</div>
                                    <div class="permission-description">{{ $permission->description }}</div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="{{ route('admin.rbac.roles.index') }}" class="btn-cancel">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
            <button type="submit" class="btn-save">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Add select all functionality for each group
document.addEventListener('DOMContentLoaded', function() {
    const groups = document.querySelectorAll('.permission-group');
    
    groups.forEach(group => {
        const header = group.querySelector('h5');
        const checkboxes = group.querySelectorAll('input[type="checkbox"]');
        
        if (checkboxes.length > 0) {
            // Add select all link
            const selectAll = document.createElement('a');
            selectAll.href = '#';
            selectAll.className = 'text-primary small';
            selectAll.innerHTML = '<i class="fas fa-check-square me-1"></i>Select All';
            selectAll.style.marginLeft = 'auto';
            selectAll.style.cursor = 'pointer';
            
            header.appendChild(selectAll);
            
            selectAll.addEventListener('click', function(e) {
                e.preventDefault();
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                });
                
                selectAll.innerHTML = allChecked ? 
                    '<i class="fas fa-square me-1"></i>Select All' : 
                    '<i class="fas fa-check-square me-1"></i>Deselect All';
            });
            
            // Update select all text when checkboxes change
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    selectAll.innerHTML = allChecked ? 
                        '<i class="fas fa-check-square me-1"></i>Deselect All' : 
                        '<i class="fas fa-square me-1"></i>Select All';
                });
            });
        }
    });
});
</script>
@endpush
