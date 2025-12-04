@extends('layouts.app')

@section('title', 'Equipment Types - System Management')
@section('page_title', 'Equipment Type Management')
@section('page_description', 'Manage equipment types and their sort order')

@section('content')
<div class="content">
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class='bx bx-arrow-back me-1'></i> Back to Settings
        </a>
        @if(auth()->user()->hasPermissionTo('settings.manage'))
        <a href="{{ route('admin.settings.equipment-types.create') }}" class="btn btn-primary btn-sm">
            <i class='bx bx-plus me-1'></i> Add Equipment Type
        </a>
        @endif
    </div>

    <!-- Search and Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.settings.equipment-types.index') }}" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Search equipment types..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.settings.equipment-types.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Equipment Types Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Equipment Count</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipmentTypes as $type)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $type->name }}</div>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $type->equipment_count }}</span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(auth()->user()->hasPermissionTo('settings.manage'))
                                <a href="{{ route('admin.settings.equipment-types.edit', $type) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class='bx bx-edit'></i>
                                </a>
                                @if($type->equipment->isEmpty())
                                <form action="{{ route('admin.settings.equipment-types.destroy', $type) }}" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary delete-btn">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <div class="empty-equipment">
                                <i class='bx bx-cog'></i>
                                <h5>No Equipment Types Found</h5>
                                <p>{{ request()->hasAny(['search']) ? 'Try adjusting your search criteria.' : 'Get started by adding your first equipment type.' }}</p>
                                @if(auth()->user()->hasPermissionTo('settings.manage') && !request()->hasAny(['search']))
                                <a href="{{ route('admin.settings.equipment-types.create') }}" class="btn btn-primary">
                                    <i class='bx bx-plus me-1'></i> Add Equipment Type
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($equipmentTypes->hasPages())
    <div class="pagination-section">
        <div class="pagination-info">
            Showing {{ $equipmentTypes->firstItem() }} to {{ $equipmentTypes->lastItem() }} of {{ $equipmentTypes->total() }} results
        </div>
        {{ $equipmentTypes->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif
</div>

@push('scripts')
<script>
// Delete confirmation with SweetAlert
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.delete-form');
            const typeName = form.closest('tr').querySelector('.fw-medium').textContent;

            Swal.fire({
                title: 'Delete Equipment Type?',
                html: `
                    <div class="text-left">
                        <p class="mb-3">Are you sure you want to delete <strong>"${typeName}"</strong>?</p>
                        <div class="bg-red-50 border border-red-200 p-3 rounded">
                            <p class="text-red-700 text-sm mb-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Warning:</strong> This action is permanent and cannot be undone.
                            </p>
                            <p class="text-red-600 text-xs">All data associated with this equipment type will be permanently removed.</p>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash mr-2"></i>Delete Permanently',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush
@endsection
