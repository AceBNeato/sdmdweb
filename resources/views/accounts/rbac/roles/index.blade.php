@extends('layouts.app')

@section('page_title', 'Role Management')
@section('page_description', 'Manage user roles and permissions')

@push('styles')
    <style>
        .rbac-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .role-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .role-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .role-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .role-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .role-description {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .permissions-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .permission-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .permission-badge.maintenance {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .permission-badge.equipment {
            background: #e8f5e8;
            color: #388e3c;
        }
        
        .permission-badge.reports {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .permission-badge.settings {
            background: #fce4ec;
            color: #c2185b;
        }
        
        .role-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-edit {
            background: #2196f3;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s ease;
        }
        
        .btn-edit:hover {
            background: #1976d2;
        }
        
        .btn-permissions {
            background: #4caf50;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s ease;
        }
        
        .btn-permissions:hover {
            background: #388e3c;
        }
        
        .role-count {
            background: #f5f5f5;
            color: #666;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .admin-badge {
            background: #ff5722;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
@endpush

@section('content')
<div class="rbac-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-2">Role Management</h1>
            <p class="text-muted">Manage user roles and their associated permissions</p>
        </div>
        <div>
            <a href="{{ route('admin.rbac.roles.permissions') }}" class="btn btn-success">
                <i class="fas fa-shield-alt me-2"></i>Manage All Permissions
            </a>
        </div>
    </div>

    <!-- Roles Grid -->
    <div class="row">
        @foreach($roles as $role)
            <div class="col-md-6 col-lg-4">
                <div class="role-card">
                    <div class="role-header">
                        <div>
                            <div class="role-name">
                                {{ $role->display_name }}
                                @if($role->name === 'super-admin' || $role->name === 'admin')
                                    <span class="admin-badge ms-2">Admin</span>
                                @endif
                            </div>
                            <div class="role-description">{{ $role->description }}</div>
                        </div>
                        <div class="role-count">{{ $role->permissions->count() }} perms</div>
                    </div>
                    
                    <div class="permissions-list">
                        @foreach($role->permissions->take(8) as $permission)
                            <span class="permission-badge {{ $permission->group }}">
                                {{ $permission->display_name }}
                            </span>
                        @endforeach
                        @if($role->permissions->count() > 8)
                            <span class="permission-badge">+{{ $role->permissions->count() - 8 }} more</span>
                        @endif
                    </div>
                    
                    <div class="role-actions">
                        @if(auth()->user()->hasPermissionTo('roles.edit'))
                            <a href="{{ route('admin.rbac.roles.edit', $role) }}" class="btn-edit">
                                <i class="fas fa-edit me-1"></i>Edit Role
                            </a>
                        @endif
                        <a href="{{ route('admin.rbac.roles.permissions') }}#role-{{ $role->id }}" class="btn-permissions">
                            <i class="fas fa-key me-1"></i>Permissions
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endsection
